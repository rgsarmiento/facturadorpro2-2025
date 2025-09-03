<?php

namespace Modules\Factcolombia1\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use Modules\Factcolombia1\Http\Resources\Tenant\AdvancedConfigurationResource;
use App\Models\Tenant\Item;
use App\Http\Controllers\Controller;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
use Modules\Factcolombia1\Http\Requests\Tenant\AdvancedConfigurationRequest;
use App\Models\Tenant\Document;
use Illuminate\Support\Facades\Log;
use Modules\Factcolombia1\Models\TenantService\Company;

class AdvancedConfigurationController extends Controller
{
    public function index()
    {
        $company = Company::firstOrFail();
        $env_service_fact = str_replace("/api/", "/", config("tenant.service_fact", env("SERVICE_FACT", "http://noapi.com")));
        $identification_number = $company->identification_number;
        return view('factcolombia1::advanced-configuration.index', compact('env_service_fact', 'identification_number'));
    }

    public function record()
    {
        $company = Company::firstOrFail();
        $record = new AdvancedConfigurationResource(AdvancedConfiguration::firstOrFail());
        $canChangeAllowSellerLogin = \DB::table('co_companies')->where('identification_number', $company->identification_number)->value('allow_seller_login');
        $data['data'] = $record->toArray(request());
        $data['data']['canChangeAllowSellerLogin'] = (bool)$canChangeAllowSellerLogin;
        return response()->json($data);
    }

    public function change_allow_seller_login($value){
        $company = Company::firstOrFail();
        $base_url = config("tenant.service_fact", "");
        $ch5 = curl_init("{$base_url}ubl2.1/change-allow-seller-login");
        $data = [
            "state"=> (bool)$value,
        ];
        $data_allow_seller_login = json_encode($data);
        curl_setopt($ch5, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch5, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch5, CURLOPT_POSTFIELDS,($data_allow_seller_login));
        curl_setopt($ch5, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch5, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch5, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response = curl_exec($ch5);
        return $response;
    }

    public function store(AdvancedConfigurationRequest $request) {
        $id = $request->input('id');
        $record = AdvancedConfiguration::find($id);
        $record->fill($request->all());
        $record->save();
        // Procesar configuración RADIAN si se están guardando datos de correo
        if ($request->has('radian_imap_host') && $request->has('radian_imap_user') &&
            $request->has('radian_imap_password') && $request->has('radian_imap_encryption') &&
            $request->has('radian_imap_port')) {
                $this->sendRadianConfigurationToAPI($request);
        }

        $response = json_decode($this->change_allow_seller_login($request->allow_seller_login));
        if($response->success)
            return [
                'success' => true,
                'message' => 'Configuración actualizada'
            ];
        else
            return [
                'success' => false,
                'message' => 'Hubo un problema al actualizar la informacion de allow_seller_login en la API...'
            ];
    }

    /**
     * Envía la configuración RADIAN al endpoint externo
     */
    private function sendRadianConfigurationToAPI($request)
    {
        try {
            // Obtener datos de las compañías
            $company = Company::first(); // TenantService\Company
            $company1 = \Modules\Factcolombia1\Models\Tenant\Company::first(); // Tenant\Company

            if (!$company || !$company1) {
                \Log::error('No se encontraron datos de compañía para enviar configuración RADIAN');
                return;
            }

            // Construir el endpoint
            $baseUrl = config('tenant.service_fact');
            $endpoint = "{$baseUrl}ubl2.1/config/{$company->identification_number}/{$company->dv}";

            // Construir el payload
            $payload = [
                "type_document_identification_id" => $company->type_document_identification_id,
                "type_organization_id" => $company->type_organization_id,
                "type_regime_id" => $company->type_regime_id,
                "type_liability_id" => $company->type_liability_id,
                "business_name" => $company1->name,
                "merchant_registration" => $company->merchant_registration,
                "municipality_id" => $company->municipality_id,
                "address" => $company->address,
                "phone" => $company->phone,
                "email" => $company1->email,
                "imap_server" => $request->radian_imap_host,
                "imap_user" => $request->radian_imap_user,
                "imap_password" => $request->radian_imap_password,
                "imap_encryption" => $request->radian_imap_encryption,
                "imap_port" => $request->radian_imap_port
            ];

            // Realizar la petición HTTP
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Log para debugging
            \Log::info('Configuración RADIAN enviada', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'response' => $response,
                'http_code' => $httpCode
            ]);

            if ($httpCode >= 200 && $httpCode < 300) {
                \Log::info('Configuración RADIAN enviada exitosamente');
            } else {
                \Log::error('Error al enviar configuración RADIAN', [
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Excepción al enviar configuración RADIAN: ' . $e->getMessage());
        }
    }

    public function deleteDocumentByResolution(Request $request)
    {
        $records = Document::where('type_document_id', $request->id)->get();

        if ($records->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No se ha encontrado registros'
            ];
        }

        $ids = $records->pluck('id')->toArray();
        Log::info('Deleted records with IDs: ' . implode(', ', $ids));

        try {
            Document::where('type_document_id', $request->id)->delete();
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'No se han eliminado registros'
            ];
        }

        return [
            'success' => true,
            'data' => $records,
            'message' => 'Han sido eliminado los documentos'
        ];
    }
}
