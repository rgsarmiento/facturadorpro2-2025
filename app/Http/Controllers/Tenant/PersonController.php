<?php
namespace App\Http\Controllers\Tenant;

use App\Http\Requests\Tenant\PersonRequest;
use App\Http\Resources\Tenant\PersonCollection;
use App\Http\Resources\Tenant\PersonResource;
use App\Imports\PersonsImport;
use App\Models\Tenant\Catalogs\Country;
use App\Models\Tenant\Catalogs\Department;
use App\Models\Tenant\Catalogs\District;
use App\Models\Tenant\Catalogs\IdentityDocumentType;
use App\Models\Tenant\Catalogs\Province;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Person;
use App\Models\Tenant\PersonType;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;

use Modules\Factcolombia1\Models\Tenant\{
    TypeIdentityDocument,
    TypePerson,
    TypeRegime,
    TypeObligation,
    Country as CoCountry,
};
use App\Exports\PersonExport;
use Carbon\Carbon;
use Goutte\Client as ClientScrap;
use Modules\Factcolombia1\Models\TenantService\Company as ServiceTenantCompany;



class PersonController extends Controller
{
    protected $nameclient;

    public function index($type)
    {
        $api_service_token = config('configuration.api_service_token');
        return view('tenant.persons.index', compact('type','api_service_token'));
    }

    public function columns()
    {
        return [
            'name' => 'Nombre',
            'number' => 'Número'
        ];
    }

    public function records($type, Request $request)
    {
      //  return 'sd';
        $records = Person::where($request->column, 'like', "%{$request->value}%")
                            ->where('type', $type)
                            ->orderBy('name');

        return new PersonCollection($records->paginate(config('tenant.items_per_page')));
    }

    /**
     * Search persons for select dropdowns (autocomplete)
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $limit = $request->get('limit', 20);

            $results = Person::where(function($q) use ($query) {
                    $q->where('number', 'like', "%{$query}%")
                      ->orWhere('name', 'like', "%{$query}%");
                })
                ->orderBy('name', 'asc')
                ->limit($limit)
                ->get(['number', 'name', 'identity_document_type_id']);

            return response()->json([
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al buscar terceros',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        return view('tenant.customers.form');
    }

    public function tables()
    {
        // $countries = Country::whereActive()->orderByDescription()->get();
        // $departments = Department::whereActive()->orderByDescription()->get();
        // $provinces = Province::whereActive()->orderByDescription()->get();
        // $districts = District::whereActive()->orderByDescription()->get();
        $identity_document_types = IdentityDocumentType::whereActive()->get();
        $person_types = PersonType::get();
        // $locations = $this->getLocationCascade();
        $api_service_token = config('configuration.api_service_token');

        $typeIdentityDocuments = TypeIdentityDocument::all();
        $typeRegimes = TypeRegime::all();
        $typePeople = TypePerson::all();
        $typeObligations = TypeObligation::all();
        $countries = CoCountry::all();

        return compact('countries', 'identity_document_types','person_types','api_service_token', 'typeIdentityDocuments', 'typeRegimes',
                        'typePeople', 'typeObligations');
    }

    public function record($id)
    {
        $record = new PersonResource(Person::findOrFail($id));

        return $record;
    }

    /**
     * Guarda (o actualiza) un cliente.
     * Antes de crear un nuevo cliente, se verifica si ya existe uno con el mismo número
     * y tipo de documento. Si existe, se retorna su ID para cargarlo en la factura.
     *
     * @param PersonRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PersonRequest $request)
    {
        // Verificamos si existe ya un registro con el mismo número y tipo de documento.
        $existing = Person::where('number', $request->number)
                          ->where('identity_document_type_id', $request->identity_document_type_id)
                          ->first();

//        if ($existing) {
//            // Si ya existe, retornamos el cliente existente para que se cargue en la factura.
//            return response()->json([
//                'success' => true,
//                'message' => 'El cliente ya existe, se cargará Automaticamente',
//                'id' => $existing->id
//            ]);
//        }

        // Si no existe, se procede a crear el cliente.
        $id = $request->input('id');
        $person = Person::firstOrNew(['id' => $id]);
        $person->fill($request->all());
        $person->save();

        // Se eliminan las direcciones viejas y se guardan las nuevas
        $person->addresses()->delete();
        $addresses = $request->input('addresses');
        foreach ($addresses as $row) {
            $person->addresses()->updateOrCreate(['id' => $row['id']], $row);
        }

        $person_type = ($person->type == 'customers') ? 'Cliente' : 'Proveedor';

        return response()->json([
            'success' => true,
            'message' => ($id) ? "{$person_type} editado con éxito" : "{$person_type} registrado con éxito",
            'id' => $person->id
        ]);
    }

    public function destroy($id)
    {
        try {

            $person = Person::findOrFail($id);
            $person_type = ($person->type == 'customers') ? 'Cliente':'Proveedor';
            $person->delete();

            return [
                'success' => true,
                'message' => $person_type.' eliminado con éxito'
            ];

        } catch (Exception $e) {

            return ($e->getCode() == '23000') ? ['success' => false,'message' => "El {$person_type} esta siendo usado por otros registros, no puede eliminar"] : ['success' => false,'message' => "Error inesperado, no se pudo eliminar el {$person_type}"];

        }

    }


    /**
     * Eliminar todos los registros que no tienen transacciones asociadas
     *
     * @return array
     */
    public function deleteAll($type)
    {

        $quantity_deleted = 0;
        $persons = Person::whereType($type)->select('id', 'name')->get();

        foreach ($persons as $person) {

            // si tienen registros asociados no se eliminan
            try {

                $person->delete();
                $quantity_deleted++;

            } catch (Exception $e){
            }

        }

        return [
            'success' => true,
            'message' => "{$quantity_deleted} registro(s) eliminados"
        ];

    }


    public function import(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                $filePath = $request->file('file')->getPathname();
                $import = new PersonsImport($filePath);
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                \Log::error($e);
                return [
                    'success' => false,
                    'message' =>  $e->getMessage(),
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    public function getLocationCascade()
    {
        $locations = [];
        $departments = Department::where('active', true)->get();
        foreach ($departments as $department)
        {
            $children_provinces = [];
            foreach ($department->provinces as $province)
            {
                $children_districts = [];
                foreach ($province->districts as $district)
                {
                    $children_districts[] = [
                        'value' => $district->id,
                        'label' => $district->description
                    ];
                }
                $children_provinces[] = [
                    'value' => $province->id,
                    'label' => $province->description,
                    'children' => $children_districts
                ];
            }
            $locations[] = [
                'value' => $department->id,
                'label' => $department->description,
                'children' => $children_provinces
            ];
        }

        return $locations;
    }


    public function enabled($type, $id)
    {

        $person = Person::findOrFail($id);
        $person->enabled = $type;
        $person->save();

        $type_message = ($type) ? 'habilitado':'inhabilitado';

        return [
            'success' => true,
            'message' => "Cliente {$type_message} con éxito"
        ];

    }


    public function coExport($type)
    {
        $records = Person::where('type', $type)
                            ->get();

        $name = $type == "customers" ? "Clientes":"Proveedores";

        return (new PersonExport)
                ->records($records)
                ->download($name.Carbon::now().'.xlsx');

    }

    public  function setNameClient($name)
    {
        $this->nameclient = $name;
    }

    public function searchName($nit)
    {
        $client = new ClientScrap();
        $crawler = $client->request('GET', "https://www.einforma.co/servlet/app/portal/ENTP/prod/LISTA_EMPRESAS/razonsocial/{$nit}");
        $crawler->filter('h1[class="title01"]')->each(function($node) {
            // dd($node->text());
            $text = $node->text();
            $marker = 'Situación de la empresa:';
            if (strpos($text, $marker) !== false) {
                $name = substr($text, 0, strpos($text, $marker));
            } else {
                $name = $text;
            }
            $name = trim($name);
            $this->setNameClient($name);
        });

        /*each(function ($node) use ($datos) {
          //  print $node->text()."\n";
            array_push($datos, $node->html());
            dd($node->text());
        });*/

        return [
            'data' => $this->nameclient
        ];
    }


    /**
     * Busqueda de cliente por id
     *
     * @param  int $id
     * @return array
     */
    public function searchCustomerById($id)
    {

        return [
            'customers' => Person::where('id', $id)
                                    ->take(1)
                                    ->get()->transform(function($row){
                                        return $row->getRowSearchResource();
                                    })
        ];

    }

    /**
     * Busqueda de clientes
     * Si no ingresan datos para búsqueda, retorna los 10 primeros (usar en método tables)
     *
     * Usado en:
     * RemissionController
     *
     * @param  Request $request
     * @return array
     */
    public function searchCustomers(Request $request)
    {

        if(!$request->has('input'))
        {
            $customers = Person::whereType('customers')->take(10);
        }
        else
        {
            $customers = Person::whereFilterSearchCustomer($request->input);
        }

        return [
            'customers' => $customers->get()->transform(function($row){
                return $row->getRowSearchResource();
            })
        ];

    }


    /**
     * Busqueda de proveedores
     * Si no ingresan datos para búsqueda, retorna los 10 primeros (usar en método tables)
     *
     * Usado en:
     * RemissionController
     *
     * @param  Request $request
     * @return array
     */
    public function searchSuppliers(Request $request)
    {
        $suppliers = (!$request->has('input')) ? Person::whereType('suppliers')->take(Person::RECORDS_ON_TABLE) : Person::whereFilterSearchSupplier($request->input);

        return [
            'suppliers' => $suppliers->get()->transform(function($row){
                return $row->getRowSearchResource();
            })
        ];
    }


    /**
     * Busqueda de registro por id
     *
     * @param  int $id
     * @return array
     */
    public function searchPersonById($id)
    {
        return Person::where('id', $id)
                    ->take(1)
                    ->get()->transform(function($row){
                        return $row->getRowSearchResource();
                    });
    }

        /**
     * Consulta el API DIAN y retorna la información del contribuyente.
     *
     * Se espera recibir en el request:
     * - identification_number: Número de identificación del contribuyente.
     * - type_document_id: Tipo de documento (p. ej.: 3 para cédula o NIT según lo requiera el API).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryDian(Request $request)
    {
        // Validación de entrada: se requiere un número de identificación y el tipo de documento.
        $data = $request->validate([
            'identification_number' => 'required|string',
            'type_document_id'       => 'required|integer',
        ]);

        // Se obtiene la información de la empresa para recuperar el token de API DIAN
        $company = ServiceTenantCompany::firstOrFail();

        // Construcción de la URL del servicio DIAN a partir de la variable de entorno configurada.
        // La URL base se define en config/tenant.php como 'service_fact'
        $baseUrl = config('tenant.service_fact'); // Obtiene la URL base: SERVICE_FACT
        $url = $baseUrl . 'ubl2.1/query_rut';       // Concatena la ruta específica del endpoint DIAN

        // Configuración de las cabeceras HTTP requeridas para la solicitud.
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            // Se incluye el token de autorización obtenido de la configuración de la empresa.
            "Authorization: Bearer {$company->api_token}"
        ];

        // Convierte los datos de entrada a formato JSON para enviarlos en la solicitud
        $payload = json_encode($data);

        // Inicializa cURL y establece las opciones para la solicitud
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      // Para capturar la respuesta
        // Se utiliza el método GET. Si el API requiere POST, cambiar el método aquí.
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
//\Log::debug($url);
//\Log::debug($payload);
//\Log::debug($company->api_token);
        // Ejecuta la llamada al API DIAN
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return response()->json([
                'success' => false,
                'message' => "Error al conectar con el API DIAN: " . $err
            ], 500);
        }

        $decodedResponse = json_decode($response, true);

        // Retorna la respuesta decodificada sin modificaciones adicionales
        return response()->json($decodedResponse);
    }

}
