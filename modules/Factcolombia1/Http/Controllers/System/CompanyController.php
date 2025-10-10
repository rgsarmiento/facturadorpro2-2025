<?php

namespace Modules\Factcolombia1\Http\Controllers\System;

use Modules\Factcolombia1\Http\Controllers\System\CompanyController;
use Modules\Factcolombia1\Jobs\Tenant\ConfigureTenantJob;
use Hyn\Tenancy\Contracts\Repositories\{
    HostnameRepository,
    WebsiteRepository
};
use Modules\Factcolombia1\Http\Controllers\Controller;
use Modules\Factcolombia1\Http\Requests\System\{
    CompanyUpdateRequest,
    CompanyRequest
};
use Modules\Factcolombia1\Models\System\Company;
use Illuminate\Http\Request;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\{
    Hostname,
    Website
};
use Modules\Factcolombia1\Models\Tenant\{
    Company as TenantCompany,
    User
};

use Modules\Factcolombia1\Models\TenantService\{
    Company as TenantServiceCompany
};
use Carbon\Carbon;
use DB;

use Modules\Factcolombia1\Models\SystemService\{
    Country as ServiceCountry,
   // Department as ServiceDepartment,
    Language as ServiceLanguage,
    Tax as ServiceTax,
    TypeEnvironment as ServiceTypeEnvironment,
    TypeOperation as ServiceTypeOperation,
    TypeDocumentIdentification as ServiceTypeDocumentIdentification,
    TypeCurrency as ServiceTypeCurrency,
    TypeOrganization as ServiceTypeOrganization,
    TypeRegime as ServiceTypeRegime,
    TypeLiability as ServiceTypeLiability,
    Department as ServiceDepartment,
    Municipality as ServiceMunicipality,
    Company as ServiceCompany
};

use App\Models\System\Module;
use Modules\Factcolombia1\Traits\System\CompanyTrait;
use Exception;
use Modules\Factcolombia1\Http\Resources\System\{
    CompanyCollection,
    CompanyResource
};
use Modules\Factcolombia1\Jobs\System\CreateTenantJob;
use Illuminate\Support\Str;


class CompanyController extends Controller
{
    use CompanyTrait;

    public function store(CompanyRequest $request) {
        // Aumentar límites por operación pesada y capturar cualquier salida accidental
        @set_time_limit(0);
        ini_set('max_execution_time', '0');
        if (ini_get('memory_limit') !== '-1') {
            ini_set('memory_limit', '512M');
        }
        if (function_exists('ob_get_level') && ob_get_level() === 0) {
            ob_start();
        }

        $response = $this->createCompanyApiDian($request);
        if(!property_exists( $response, 'password' ) || !property_exists( $response, 'token' )){
            // Log detallado para facilitar depuración cuando la API rechaza la creación
            try {
                \Log::warning('ApiDIAN: fallo al registrar compañía', [
                    'identification_number' => $request->identification_number,
                    'subdomain' => $request->subdomain,
                    'http_code' => is_object($response) && property_exists($response, '_http_code') ? $response->_http_code : null,
                    'api_message' => is_object($response) && property_exists($response, 'message') ? $response->message : null,
                    'errors' => is_object($response) && property_exists($response, 'errors') ? $response->errors : null,
                    'raw' => is_object($response) && property_exists($response, '_raw') ? mb_substr($response->_raw, 0, 500) : null,
                ]);
            } catch (\Throwable $t) {}
            $payload = [
                'message' => "Error al registrar Compañía en ApiDian",
                'response' => $response,
                'success' => false
            ];
            return $this->safeJson($payload, 422);
        }
        $request->api_token = $response->token;

    DB::connection('system')->beginTransaction();

        try {
            $subDom = strtolower($request->input('subdomain'));
            $uuid = config('tenant.prefix_database').'_'.$subDom;
            $fqdn = $subDom.'.'.config('tenant.app_url_base');

            // Website
            $website = new Website;
            $website->uuid = $uuid;

            $this->validateWebsite($uuid, $website);

            app(WebsiteRepository::class)->create($website);

            // Hostname
            $hostname = new Hostname;
            $hostname->fqdn = $fqdn;
            $hostname = app(HostnameRepository::class)->create($hostname);

            app(HostnameRepository::class)->attach($hostname, $website);

            $company = $this->createSystemCompany($request, $hostname);

            // Switch
            $tenancy = app(Environment::class);
            $tenancy->tenant($website);

            // Iniciamos transacción manual del tenant sólo una vez y controlamos el estado
            DB::connection('tenant')->beginTransaction();

        }
        catch (Exception $e) {

            DB::connection('system')->rollBack();

            return $this->safeJson([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);

        }

        try {

            // Ejecutar procesos del tenant sin iniciar transacciones internas en seeders
            $this->runTenantPeruSeeder($request);
            $this->runTenantSeeder($request, $response, $company);


            if (DB::connection('tenant')->transactionLevel() > 0) {
                DB::connection('tenant')->commit();
            }
            if (DB::connection('system')->transactionLevel() > 0) {
                DB::connection('system')->commit();
            }

        }
        catch (Exception $e) {

            if (DB::connection('tenant')->transactionLevel() > 0) {
                try { DB::connection('tenant')->rollBack(); } catch (\Throwable $ignored) {}
            }
            if (DB::connection('system')->transactionLevel() > 0) {
                try { DB::connection('system')->rollBack(); } catch (\Throwable $ignored) {}
            }

            return $this->safeJson([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);

        }

        // Switch
        $tenancy = app(Environment::class);
        $tenancy->tenant(app(\Hyn\Tenancy\Environment::class)->website());

        config(['database.default' => 'system']);

        //dispatch((new ConfigureTenantJob)->onTenant($website->id)); ya no estara en cola

        return $this->safeJson([
            'message' => "Se registro con éxito la compañía {$company->name}.",
            'company' => $company,
            'success' => true
        ]);

    }

    public function currentUserId()
    {
        $currentUserId = auth()->id();
        return response()->json(['currentUserId' => $currentUserId]);
    }


    public function validateWebsite($uuid, $website){

        $exists = $website::where('uuid', $uuid)->first();

        if($exists){
            throw new Exception("El subdominio ya se encuentra registrado");
        }

    }

    /*

    // funcion para cambiar de secion hacia empresa tenancy por cristian

    public function switchTenant($companyId)
    {
        $company = Company::findOrFail($companyId);
        // Asegúrate de que la relación o método 'hostname' exista y devuelva el objeto esperado
        $hostname = $company->hostname;

        // Obtiene el usuario administrador autenticado
        $user = auth()->user();

        // Verifica si el usuario tiene un token; si no, crea uno nuevo
        if (empty($user->api_token)) {
            $user->api_token = Str::random(60); // Usa Str::random o cualquier otro método que prefieras para generar el token
            $user->save();
        }

        // Prepara la URL para redireccionar al tenant
        $urlToRedirect = "http://{$hostname->fqdn}/switch-tenant?token={$user->api_token}";

        // Redirecciona al usuario
        return redirect()->away($urlToRedirect);
    }
    */


    public function switchTenant($companyId)
    {
        // Obtener la compañía y su hostname asociado
        $company = Company::findOrFail($companyId);
        $hostname = $company->hostname; // Asume que tienes una relación o atributo

       // dd($company->subdomain);

       //datos de configuración de da la base de datos host,base de datos, contraseña etc
        $environment = app(Environment::class);
        $environment->tenant($company->website);


        $tenantDatabaseName = 'tenancy_' . $company->subdomain; // O cualquier lógica que utilices para nombrar las bases de datos


        config([
            'database.connections.tenant' => [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => $tenantDatabaseName,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'unix_socket' => config('database.connections.mysql.unix_socket'),
                'charset' => config('database.connections.mysql.charset'),
                'collation' => config('database.connections.mysql.collation'),
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ],
        ]);

        // Realizar una consulta utilizando la conexión 'tenant' y obtener el primer registro de la tabla 'users'
        $firstUser = DB::connection('tenant')->table('co_service_companies')->first();
        $apiToken = $firstUser->api_token;

         // Encripta el token antes de agregarlo a la URL
         $encryptedToken = encrypt($apiToken);

        // Genera la URL con el token encriptado como parámetro
        $urlToRedirect = "http://{$hostname->fqdn}/dashboard?api_token={$encryptedToken}";

        // Redirecciona al usuario al tenant con el token incluido en la URL
        return redirect()->away($urlToRedirect);
    }


/*
    public function records()
    {

        $records = Company::latest()->get();

        foreach ($records as &$row) {
            $tenancy = app(Environment::class);
            $tenancy->tenant($row->hostname->website);
            // $row->count_doc = DB::connection('tenant')->table('documents')->count();
            $row->count_doc = DB::connection('tenant')->table('configurations')->first()->quantity_documents;
            //$row->count_user = DB::connection('tenant')->table('users')->count();

            if($row->start_billing_cycle)
            {
                $day_start_billing = date_format($row->start_billing_cycle, 'j');
                $day_now = (int)date('j');


                if( $day_now <= $day_start_billing  )
                {
                    $init = Carbon::parse( date('Y').'-'.((int)date('n') -1).'-'.$day_start_billing );
                    $end = Carbon::parse(date('Y-m-d'));

                    $row->count_doc_month = DB::connection('tenant')->table('documents')->whereBetween('date_of_issue', [ $init, $end  ])->count();
                }
                else{

                    $init = Carbon::parse( date('Y').'-'.((int)date('n') ).'-'.$day_start_billing );
                    $end = Carbon::parse(date('Y-m-d'));
                    $row->count_doc_month = DB::connection('tenant')->table('documents')->whereBetween('date_of_issue', [ $init, $end  ])->count();

                }

            }
        }

        return new CompanyCollection($records);
    }
*/

    public function records(){
        //    \Log::debug("A");
        // Obtener el ID del usuario autenticado
        $userId = auth()->id();
        if (in_array($userId, [1, 2, 3, 4, 5, 6, 7, 8, 9])) {
            // Para los usuarios con ID 1 y 2, obtener todas las empresas
            $records = Company::latest()->get();
        }
        else {
            // Obtener los identification_number asociados con el usuario autenticado
            $identificationNumbers = ServiceCompany::where('user_id', $userId)
                                                ->pluck('identification_number');
            // Filtrar las Company por los identification_number obtenidos
            $records = Company::whereIn('identification_number', $identificationNumbers)
                              ->latest()
                              ->get();
        }
        // Procesar cada registro de Company obtenido
        foreach ($records as &$row) {
            $tenancy = app(Environment::class);
            $tenancy->tenant($row->hostname->website);
            // $row->count_doc = DB::connection('tenant')->table('documents')->count();
            $row->count_doc = DB::connection('tenant')->table('configurations')->first()->quantity_documents;
            //$row->count_user = DB::connection('tenant')->table('users')->count();
            if($row->start_billing_cycle){
                $day_start_billing = date_format($row->start_billing_cycle, 'j');
                $day_now = (int)date('j');
                if( $day_now <= $day_start_billing  )
                {
                    $init = Carbon::parse( date('Y').'-'.((int)date('n') -1).'-'.$day_start_billing );
                    $end = Carbon::parse(date('Y-m-d'));
                    $row->count_doc_month = DB::connection('tenant')->table('documents')->whereBetween('date_of_issue', [ $init, $end  ])->count();
                }
                else{
                    $init = Carbon::parse( date('Y').'-'.((int)date('n') ).'-'.$day_start_billing );
                    $end = Carbon::parse(date('Y-m-d'));
                    $row->count_doc_month = DB::connection('tenant')->table('documents')->whereBetween('date_of_issue', [ $init, $end  ])->count();
                }
            }
        }
        // Devolver la colección de Company procesadas
        return new CompanyCollection($records);
    }

    public function record($id){
        $company = Company::findOrFail($id);
        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        $company->modules = DB::connection('tenant')->table('module_user')->where('user_id', 1)->get();
        return new CompanyResource($company);
    }


    /**
     * All
     * @return \Illuminate\Http\Response
     */
    public function all() {
        return  [
                    'company' =>  Company::all(),
                    'servicecompany' => ServiceCompany::all()
                ];
    }

    /**
     * Update
     * @param  \App\Models\System\Company $company
     * @param  \App\Http\Requests\System\CompanyUpdateRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(CompanyUpdateRequest $request) {
        $response = $this->createCompanyApiDian($request);

        if(!property_exists( $response, 'password' ) || !property_exists( $response, 'token' )){
            return [
                'message' => "Error al actualizar compañía en ApiDian",
                'response' => $response,
                'success' => false
            ];
        }

        $company = Company::findOrFail($request->id);

        $company->update([
            'limit_documents' => $request->limit_documents,
            'limit_users' => $request->limit_users,
            'economic_activity_code' => $request->economic_activity_code,
            'ica_rate' => $request->ica_rate
        ]);

        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        DB::connection('tenant')->table('configurations')->where('id', 1)->update(['limit_users' => $company->limit_users]);

        ServiceCompany::where('identification_number', $company->identification_number)->first()
            ->update(
                [
                    'type_document_identification_id' => $request->type_document_identification_id,
                    'department_id' => $request->department_id,
                    'type_organization_id' => $request->type_organization_id,
                    'type_regime_id' => $request->type_regime_id,
                    'municipality_id' => $request->municipality_id,
                    'merchant_registration' => $request->merchant_registration,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'type_liability_id' => $request->type_liability_id,
                    'api_token' => $response->token,
                ]
            );


        app(Environment::class)
            ->tenant($company->hostname->website);

        TenantCompany::firstOrFail()
            ->update([
                'limit_documents' => $request->limit_documents,
                'economic_activity_code' => $request->economic_activity_code,
                'ica_rate' => $request->ica_rate
            ]);

        if ($request->password != null) {
            User::firstOrFail()
                ->update([
                    'password' => bcrypt($request->password),
                ]);
        }

        TenantServiceCompany::firstOrFail()
            ->update(
                [
                    'type_document_identification_id' => $request->type_document_identification_id,
                    'department_id' => $request->department_id,
                    'type_organization_id' => $request->type_organization_id,
                    'type_regime_id' => $request->type_regime_id,
                    'municipality_id' => $request->municipality_id,
                    'merchant_registration' => $request->merchant_registration,
                    'address' => $request->address,
                    'phone' => $request->phone,
                    'type_liability_id' => $request->type_liability_id,
                ]
            );

        //modules
        DB::connection('tenant')->table('module_user')->where('user_id', 1)->delete();
        DB::connection('tenant')->table('module_level_user')->where('user_id', 1)->delete();

        $array_modules = [];

        foreach ($request->modules as $module) {
            if($module['checked']){
                $array_modules[] = ['module_id' => $module['id'], 'user_id' => 1];

                if($module['id'] == 1){
                    DB::connection('tenant')->table('module_level_user')->insert([
                        ['module_level_id' => 1, 'user_id' => 1],
                        ['module_level_id' => 2, 'user_id' => 1],
                        // ['module_level_id' => 3, 'user_id' => 1],
                        // ['module_level_id' => 4, 'user_id' => 1],
                        ['module_level_id' => 5, 'user_id' => 1],
                        // ['module_level_id' => 6, 'user_id' => 1],
                        ['module_level_id' => 7, 'user_id' => 1],
                        ['module_level_id' => 8, 'user_id' => 1],
                        ['module_level_id' => 9, 'user_id' => 1],
                        ['module_level_id' => 10, 'user_id' => 1],
                    ]);
                }
            }
        }

        DB::connection('tenant')->table('module_user')->insert($array_modules);
        //modules

        return [
            'message' => "Se actualizo con éxito la compañía {$company->name}.",
            'success' => true
        ];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company) {

        $hostname = Hostname::findOrFail($company->hostname_id);
        $website = Website::findOrFail($hostname->website_id);

        app(HostnameRepository::class)
            ->delete($hostname, true);

        app(WebsiteRepository::class)
            ->delete($website, true);

        DB::table('co_service_companies')->where('identification_number', $company->identification_number)->delete();
        Company::destroy($company->id);

        $this->deleteApi($company);

        return [
            'success' => true,
            'message' => "Se elimino la compañía {$company->name}."
        ];

    }

    public function deleteApi($company)
    {
        $base_url = config('tenant.service_fact');
        $number = $company->identification_number;
        $email = $company->email;
        $ch = curl_init("{$base_url}ubl2.1/config/delete/{$number}/{$email}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        $respuesta = json_decode($response);
    }

    public function updateUser(Request $request)
        {
            $identification_number = $request->input('identification_number');
            $user_id = $request->input('user_id');

            DB::table('co_service_companies')
                ->where('identification_number', $identification_number)
                ->update(['user_id' => $user_id]);

            return [
                'success' => true,
                'message' => "Se actualizó el usuario de la compañía con identificación {$identification_number}."
            ];
        }



    public function tables()
    {

        $id_country = 46; //colombia
        $department = ServiceDepartment::where('country_id', $id_country)->get();
        return [
            //'country' => ServiceCountry::all(),
            'departments' => $department,
            'municipalities' => ServiceMunicipality::whereIn('department_id',  $department->pluck('id'))->get(),
         //   'language' => ServiceLanguage::all(),
          //  'tax' => ServiceTax::all(),
           // 'type_enviroment' => ServiceTypeEnvironment::all(),
          //  'type_operation' => ServiceTypeOperation::all(),
            'type_document_identifications' => ServiceTypeDocumentIdentification::all(),
          //  'type_currency' => ServiceTypeCurrency::all(),
            'type_organizations' => ServiceTypeOrganization::all(),
            'type_regimes' => ServiceTypeRegime::all(),
            // 'modules' => Module::whereIn('id', [1,2,4,5,6,7,8,10,12])->orderBy('description')->get(),
            'modules' => Module::whereIn('id', auth()->user()->getAllowedModulesForSystem())->orderBy('description')->get(),
            'url_base' => '.'.config('tenant.app_url_base'),
            'type_liabilities' => ServiceTypeLiability::all()
        ];
    }


    public function cascade(Request $request)
    {
      $name = $request->name;
      $value = $request->value;
      $data = [];

      switch ($name) {
          case 'country':
              $data = ServiceDepartment::where('country_id', $value)->get();
              break;
          case 'department':
              $data = ServiceMunicipality::where('department_id', $value)->get();
              break;
      }

      return $data;
    }


    public function getInformationDocument($nit, $desde = NULL, $hasta = NULL)
    {
        $base_url = config('tenant.service_fact');
        $ch2 = curl_init("{$base_url}information/{$nit}/{$desde}/{$hasta}");

        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
        ));
        $response_data = curl_exec($ch2);
        $err = curl_error($ch2);
        curl_close($ch2);
       // $response_encode = json_decode($response_data);
        if($err){
            return [
                'success' => false,
                'message'=> 'Error en Api'
            ];
        }
        else{
            return $response_data;
        }

    }



    public function lockedUser(Request $request){

        $company = Company::findOrFail($request->id);
        $company->locked_users = $request->locked_users;
        $company->save();

        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_users' => $company->locked_users]);

        return [
            'success' => true,
            'message' => ($company->locked_users) ? 'Limitar creación de usuarios activado' : 'Limitar creación de usuarios desactivado'
        ];

    }


    public function lockedEmission(Request $request){

        $company = Company::findOrFail($request->id);
        $company->locked_emission = $request->locked_emission;
        $company->save();

        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_emission' => $company->locked_emission]);

        return [
            'success' => true,
            'message' => ($company->locked_emission) ? 'Limitar emisión de documentos activado' : 'Limitar emisión de documentos desactivado'
        ];

    }

    public function lockedTenant(Request $request){
        $company = Company::findOrFail($request->id);
        $company->locked_tenant = $request->locked_tenant;
        $company->save();
        $tenancy = app(Environment::class);
        $tenancy->tenant($company->hostname->website);
        DB::connection('tenant')->table('configurations')->where('id', 1)->update(['locked_tenant' => $company->locked_tenant]);
        return [
            'success' => true,
            'message' => ($company->locked_tenant) ? 'Cuenta bloqueada' : 'Cuenta desbloqueada'
        ];
    }

    public function changeAllowSellerLogin(Request $request){
        $company = Company::findOrFail($request->id);
        $service_company = ServiceCompany::where('identification_number', $company->identification_number)->first();
        $company->allow_seller_login = $request->allow_seller_login;
        $company->save();
        if(!$company->allow_seller_login) {
            $tenancy = app(Environment::class);
            $tenancy->tenant($company->hostname->website);
            DB::connection('tenant')->table('co_advanced_configuration')->where('id', 1)->update(['allow_seller_login' => false]);
            $base_url = env("SERVICE_FACT", "");
            $ch5 = curl_init("{$base_url}ubl2.1/change-allow-seller-login");
            $data = [
                "state"=> false,
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
                "Authorization: Bearer {$service_company->api_token}"
            ));
            $response = curl_exec($ch5); // Log de depuración removido
        }
        return [
            'success' => true,
            'message' => ($company->allow_seller_login) ? 'Allow Seller Login activado' : 'Allow Seller Login desactivado'
        ];
    }

    public function startBillingCycle(Request $request)
    {
        $client = Company::findOrFail($request->id);
        $client->start_billing_cycle = $request->start_billing_cycle;
        $client->save();

        return [
            'success' => true,
            'message' => ($client->start_billing_cycle) ? 'Ciclo de Facturacion definido.' : 'No se pudieron guardar los cambios.'
        ];
    }

    /**
     * Enviar JSON limpio sin mezclar con posibles salidas accidentales (echo/print) que puedan romper HTTP/2.
     */
    private function safeJson(array $payload, int $status = 200)
    {
        try {
            if (function_exists('ob_get_length') && ob_get_length()) {
                $out = ob_get_clean();
                if (!empty($out)) {
                    \Log::warning('Salida capturada durante creación de compañía (descartada)', [
                        'len' => strlen($out),
                        'preview' => mb_substr($out, 0, 500)
                    ]);
                }
            } elseif (function_exists('ob_get_level') && ob_get_level() > 0) {
                @ob_end_clean();
            }
        } catch (\Throwable $t) {
            // Ignorar problemas al limpiar buffers
        }

        return response()->json($payload, $status, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Start async company creation. Returns 202 + job (task) id immediately.
     */
    public function start(Request $request)
    {
        // Validate using existing CompanyRequest rules
    /** @var CompanyRequest $validator */
    $validator = app(CompanyRequest::class);
    $rules = $validator->rules();
    // Validar para responder 422 si corresponde, pero no descartar campos no listados en rules
    $this->validate($request, $rules);
    $data = $request->all();

        // Use client-provided task id when available to allow polling after local timeouts
        $taskId = $request->input('client_task_id');
        if (!is_string($taskId) || !preg_match('/^[0-9a-fA-F-]{36}$/', $taskId)) {
            $taskId = (string) Str::uuid();
        }

        // Initialize task status file
        $this->writeTaskStatus($taskId, [
            'id' => $taskId,
            'status' => 'queued',
            'message' => 'Tarea en cola',
            'progress' => 0,
            'result' => null,
            'queued_at' => date('c'),
        ]);

        // Dispatch job after the HTTP response has been sent (Laravel 5.7 compatible)
        // Using the application's terminating callback ensures the response is flushed first,
        // then the job runs (even with sync driver) to avoid request timeouts.
        app()->terminating(function () use ($data, $taskId) {
            dispatch(new CreateTenantJob($data, $taskId));
        });

        return $this->safeJson([
            'success' => true,
            'id' => $taskId,
            'message' => 'Creación encolada'
        ], 202);
    }

    /**
     * Get async job status by id.
     */
    public function status(string $id)
    {
        $task = $this->readTaskStatus($id);
        if (!$task) {
            return $this->safeJson([
                'success' => false,
                'message' => 'Tarea no encontrada',
            ], 404);
        }
        return $this->safeJson([
            'success' => true,
            'task' => $task,
        ]);
    }

    /**
     * Quick existence check by subdomain.
     */
    public function existsBySubdomain(Request $request)
    {
        $value = strtolower((string) $request->query('value', ''));
        if ($value === '') {
            return $this->safeJson([
                'success' => false,
                'message' => 'Parámetro value requerido'
            ], 422);
        }
        $company = Company::where('subdomain', $value)->first();
        return $this->safeJson([
            'success' => true,
            'exists' => !!$company,
            'id' => $company->id ?? null,
        ]);
    }

    private function taskDir(): string
    {
        return storage_path('app/tenant_creation_tasks');
    }

    private function writeTaskStatus(string $id, array $status): void
    {
        $dir = $this->taskDir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $file = $dir . '/' . $id . '.json';
        @file_put_contents($file, json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function readTaskStatus(string $id): ?array
    {
        $file = $this->taskDir() . '/' . $id . '.json';
        if (!file_exists($file)) return null;
        $content = @file_get_contents($file);
        if ($content === false) return null;
        $data = json_decode($content, true);
        return is_array($data) ? $data : null;
    }

}
