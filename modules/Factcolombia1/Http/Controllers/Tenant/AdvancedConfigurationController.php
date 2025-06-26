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
