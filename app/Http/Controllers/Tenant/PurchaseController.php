<?php

namespace App\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Person;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\Models\Tenant\Catalogs\ChargeDiscountType;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\PurchaseItem;
use Modules\Purchase\Models\PurchaseOrder;

use App\CoreFacturalo\Requests\Inputs\Common\LegendInput;
use App\Models\Tenant\Item;
use App\Http\Resources\Tenant\PurchaseCollection;
use App\Http\Resources\Tenant\PurchaseResource;
use App\Models\Tenant\Catalogs\AffectationIgvType;
use App\Models\Tenant\Catalogs\DocumentType;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Catalogs\PriceType;
use App\Models\Tenant\Catalogs\SystemIscType;
use App\Models\Tenant\Catalogs\AttributeType;
use App\Models\Tenant\Company;
use App\Http\Requests\Tenant\PurchaseRequest;
use App\Http\Requests\Tenant\PurchaseImportRequest;

use Illuminate\Support\Str;
use App\CoreFacturalo\Requests\Inputs\Common\PersonInput;
use App\Models\Tenant\PaymentMethodType;
use Carbon\Carbon;
use Modules\Inventory\Models\Warehouse;
use App\Models\Tenant\InventoryKardex;
use App\Models\Tenant\ItemWarehouse;
use Modules\Finance\Traits\FinanceTrait;
use Modules\Item\Models\ItemLotsGroup;
use Modules\Factcolombia1\Models\Tenant\{
    Currency,
    Tax,
};
use Barryvdh\DomPDF\Facade as PDF;

class PurchaseController extends Controller
{

    use FinanceTrait;

    public function index()
    {
        return view('tenant.purchases.index');
    }


    public function create($purchase_order_id = null)
    {
        return view('tenant.purchases.form', compact('purchase_order_id'));
    }

    public function columns()
    {
        return [
            'number' => 'Número',
            'date_of_issue' => 'Fecha de emisión',
            'date_of_due' => 'Fecha de vencimiento',
            'date_of_payment' => 'Fecha de pago',
            'name' => 'Nombre proveedor',
        ];
    }

    public function records(Request $request)
    {

        $records = $this->getRecords($request);

        return new PurchaseCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function getRecords($request){

        switch ($request->column) {
            case 'name':

                $records = Purchase::whereHas('supplier', function($query) use($request){
                                return $query->where($request->column, 'like', "%{$request->value}%");
                            })
                            ->whereTypeUser()
                            ->latest();

                break;

            case 'date_of_payment':

                $records = Purchase::whereHas('purchase_payments', function($query) use($request){
                                return $query->where($request->column, 'like', "%{$request->value}%");
                            })
                            ->whereTypeUser()
                            ->latest();

                break;

            default:

                $records = Purchase::where($request->column, 'like', "%{$request->value}%")
                            ->whereTypeUser()
                            ->latest();

                break;
        }

        return $records;

    }

    public function tables()
    {
        $suppliers = $this->table('suppliers');
        $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
        // $currency_types = CurrencyType::whereActive()->get();
        $document_types_invoice = DocumentType::whereIn('id', ['01', 'GU75', 'NE76'])->get();
        $document_types_notes = DocumentType::whereIn('id', ['07', '08'])->get();
        // $discount_types = ChargeDiscountType::whereType('discount')->whereLevel('item')->get();
        // $charge_types = ChargeDiscountType::whereType('charge')->whereLevel('item')->get();
        $company = Company::active();
        $payment_method_types = PaymentMethodType::all();
        $payment_destinations = $this->getPaymentDestinations();
        $customers = $this->getPersons('customers');

        $currencies = Currency::all();
        $taxes = $this->table('taxes');

        return compact('suppliers', 'establishment','currencies',
                    'taxes', 'document_types_invoice','company','payment_method_types', 'payment_destinations', 'customers', 'document_types_notes');
    }

    public function item_tables()
    {
        ini_set('memory_limit', '-1');
        $items = $this->table('items');
        $taxes = $this->table('taxes');
        $categories = [];
        // $affectation_igv_types = AffectationIgvType::whereActive()->get();
        // $system_isc_types = SystemIscType::whereActive()->get();
        // $price_types = PriceType::whereActive()->get();
        // $discount_types = ChargeDiscountType::whereType('discount')->whereLevel('item')->get();
        // $charge_types = ChargeDiscountType::whereType('charge')->whereLevel('item')->get();
        // $attribute_types = AttributeType::whereActive()->orderByDescription()->get();
        $warehouses = Warehouse::all();

        return compact('items', 'categories', 'taxes','warehouses');
    }

    /**
     * Devuelve solo warehouses y taxes para mejorar performance
     */
    public function getWarehousesAndTaxes()
    {
        $taxes = $this->table('taxes');
        $warehouses = Warehouse::all();

        return compact('taxes', 'warehouses');
    }

    public function record($id)
    {

        $record = new PurchaseResource(Purchase::findOrFail($id));

        return $record;
    }

    public function edit($id)
    {
        $resourceId = $id;
        return view('tenant.purchases.form_edit', compact('resourceId'));
    }

    public function note($id)
    {
        $resourceId = $id;
        return view('tenant.purchases.note', compact('resourceId'));
    }

    public function store(PurchaseRequest $request)
    {
        $data = self::convert($request);

        $purchase = DB::connection('tenant')->transaction(function () use ($data) {
            $doc = Purchase::create($data);
            foreach ($data['items'] as $row)
            {
                // $doc->items()->create($row);
                $p_item = new PurchaseItem;
                $p_item->fill($row);
                $p_item->purchase_id = $doc->id;
                $p_item->save();

                if(array_key_exists('lots', $row)){
                    foreach ($row['lots'] as $lot){
                        $p_item->lots()->create([
                            'date' => $lot['date'],
                            'series' => $lot['series'],
                            'item_id' => $row['item_id'],
                            'warehouse_id' => $row['warehouse_id'],
                            'has_sale' => false,
                            'state' => $lot['state']
                        ]);
                    }
                }

                if(array_key_exists('item', $row))
                {
                    if( $row['item']['lots_enabled'] == true)
                    {
                        ItemLotsGroup::create([
                            'code'  => $row['lot_code'],
                            'quantity'  => $row['quantity'],
                            'date_of_due'  => $row['date_of_due'],
                            'item_id' => $row['item_id']
                        ]);
                    }
                }
            }

            foreach ($data['payments'] as $payment) {
                $record_payment = $doc->purchase_payments()->create($payment);
                if(isset($payment['payment_destination_id'])){
                    $this->createGlobalPayment($record_payment, $payment);
                }
            }
            return $doc;
        });

        return [
            'success' => true,
            'data' => [
                'id' => $purchase->id,
                'number_full' => "{$purchase->series}-{$purchase->number}",
            ],
        ];
    }

    public function update(PurchaseRequest $request)
    {
        $purchase = DB::connection('tenant')->transaction(function () use ($request) {

            $doc = Purchase::firstOrNew(['id' => $request['id']]);
           // return json_encode($doc);
            $doc->fill($request->all());
            $doc->save();

            $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
            //proceso para eliminar los actualizar el stock de proiductos
            foreach ($doc->items as $item) {
                $item->purchase->inventory_kardex()->create([
                    'date_of_issue' => date('Y-m-d'),
                    'item_id' => $item->item_id,
                    'warehouse_id' => $establishment->id,
                    'quantity' => -$item->quantity,
                ]);
                $wr = ItemWarehouse::where([['item_id', $item->item_id],['warehouse_id', $establishment->id]])->first();
                $wr->stock =  $wr->stock - $item->quantity;
                $wr->save();
            }

            foreach ($doc->items()->get() as $it) {
                // dd($it);
                $it->lots()->delete();
            }

            $doc->items()->delete();

            foreach ($request['items'] as $row)
            {
                // $doc->items()->create($row);
                $p_item = new PurchaseItem;
                $p_item->fill($row);
                $p_item->purchase_id = $doc->id;
                $p_item->save();

                if(array_key_exists('lots', $row)){

                    foreach ($row['lots'] as $lot){

                        $p_item->lots()->create([
                            'date' => $lot['date'],
                            'series' => $lot['series'],
                            'item_id' => $row['item_id'],
                            'warehouse_id' => $row['warehouse_id'],
                            'has_sale' => false
                        ]);

                    }
                }
            }

            // $doc->purchase_payments()->delete();
            $this->deleteAllPayments($doc->purchase_payments);

            foreach ($request['payments'] as $payment) {

                $record_payment = $doc->purchase_payments()->create($payment);

                if(isset($payment['payment_destination_id'])){
                    $this->createGlobalPayment($record_payment, $payment);
                }

                if(isset($payment['payment_filename'])){
                    $record_payment->payment_file()->create([
                        'filename' => $payment['payment_filename']
                    ]);
                }
            }

            return $doc;
        });

        return [
            'success' => true,
            'data' => [
                'id' => $purchase->id,
            ],
        ];
    }


    public function anular($id)
    {
        $obj =  Purchase::find($id);
        $obj->state_type_id = 11;
        $obj->save();

        $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
        $warehouse = Warehouse::where('establishment_id',$establishment->id)->first();

        //proceso para eliminar los actualizar el stock de proiductos
        foreach ($obj->items as $item) {
            $item->purchase->inventory_kardex()->create([
                'date_of_issue' => date('Y-m-d'),
                'item_id' => $item->item_id,
                'warehouse_id' => $establishment->id,
                'quantity' => -$item->quantity,
            ]);
            $wr = ItemWarehouse::where([['item_id', $item->item_id],['warehouse_id', $warehouse->id]])->first();
            $wr->stock =  $wr->stock - $item->quantity;
            $wr->save();
        }

        return [
            'success' => true,
            'message' => 'Compra anulada con éxito'
        ];
    }

    public static function convert($inputs)
    {
        $company = Company::active();
        $values = [
            'user_id' => auth()->id(),
            'external_id' => Str::uuid()->toString(),
            'supplier' => Person::with('typePerson', 'typeRegime', 'identity_document_type', 'country', 'department', 'city')->findOrFail($inputs['supplier_id']),
            'soap_type_id' => $company->soap_type_id,
            'group_id' => ($inputs->document_type_id === '01') ? '01':'02',
            'state_type_id' => '01'
        ];

        // Procesar items para asegurar que item_id esté presente
        if (isset($inputs['items']) && is_array($inputs['items'])) {
            $processedItems = [];
            foreach ($inputs['items'] as $item) {
                // Si no hay item_id pero sí hay un objeto item con id, extraerlo
                if (!isset($item['item_id']) && isset($item['item']['id'])) {
                    $item['item_id'] = $item['item']['id'];
                }
                $processedItems[] = $item;
            }
            $inputs['items'] = $processedItems;
        }

        $inputs->merge($values);

        return $inputs->all();
    }

    public function table($table)
    {
        switch ($table) {

            case 'taxes':

                return Tax::all()->transform(function($row) {
                    return [
                        'id' => $row->id,
                        'name' => $row->name,
                        'code' => $row->code,
                        'rate' =>  $row->rate,
                        'conversion' =>  $row->conversion,
                        'is_percentage' =>  $row->is_percentage,
                        'is_fixed_value' =>  $row->is_fixed_value,
                        'is_retention' =>  $row->is_retention,
                        'in_base' =>  $row->in_base,
                        'in_tax' =>  $row->in_tax,
                        'type_tax_id' =>  $row->type_tax_id,
                        'type_tax' =>  $row->type_tax,
                        'retention' =>  0,
                        'total' =>  0,
                    ];
                });
                break;

            case 'suppliers':

                $suppliers = Person::whereType('suppliers')->orderBy('name')->get()->transform(function($row) {
                    return [
                        'id' => $row->id,
                        'description' => $row->number.' - '.$row->name,
                        'name' => $row->name,
                        'number' => $row->number,
                        'perception_agent' => (bool) $row->perception_agent,
                        'identity_document_type_id' => $row->identity_document_type_id,
                        'address' =>  $row->address,
                        'email' =>  $row->email,
                        'telephone' =>  $row->telephone,
                    ];
                });
                return $suppliers;

                break;

            case 'items':

                $items = Item::whereNotIsSet()->whereIsActive()->orderBy('name')->get(); //whereWarehouse()
                return collect($items)->transform(function($row) {
                    $full_description = ($row->internal_id)?$row->internal_id.' - '.$row->name:$row->name;
                    return [
                        'id' => $row->id,
                        'item_code'  => $row->item_code,
                        'name'  => $row->name,
                        'description'  => $row->description,
                        'full_description' => $full_description,
                        'currency_type_id' => $row->currency_type_id,
                        'currency_type_symbol' => $row->currency_type->symbol,
                        'sale_unit_price' => $row->sale_unit_price,
                        'purchase_unit_price' => $row->purchase_unit_price,
                        'unit_type_id' => $row->unit_type_id,
                        'purchase_tax_id' => $row->purchase_tax_id,
                        'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                        'has_perception' => (bool) $row->has_perception,
                        'lots_enabled' => (bool) $row->lots_enabled,
                        'percentage_perception' => $row->percentage_perception,
                        'item_unit_types' => collect($row->item_unit_types)->transform(function($row) {
                            return [
                                'id' => $row->id,
                                'description' => "{$row->description}",
                                'item_id' => $row->item_id,
                                'unit_type_id' => $row->unit_type_id,
                                'unit_type' => $row->unit_type,
                                'quantity_unit' => $row->quantity_unit,
                                'price1' => $row->price1,
                                'price2' => $row->price2,
                                'price3' => $row->price3,
                                'price_default' => $row->price_default,
                            ];
                        }),
                        'series_enabled' => (bool) $row->series_enabled,
                        'unit_type' => $row->unit_type,
                        'tax' => $row->tax,

                        // 'warehouses' => collect($row->warehouses)->transform(function($row) {
                        //     return [
                        //         'warehouse_id' => $row->warehouse->id,
                        //         'warehouse_description' => $row->warehouse->description,
                        //         'stock' => $row->stock,
                        //     ];
                        // })
                    ];
                });
//                return $items;

                break;
            default:

                return [];

                break;
        }
    }

    public function delete($id)
    {

        try {

            DB::connection('tenant')->transaction(function () use ($id) {

                $row = Purchase::findOrFail($id);
                $this->deleteAllPayments($row->purchase_payments);
                $row->delete();

            });

            return [
                'success' => true,
                'message' => 'Compra eliminada con éxito'
            ];

        } catch (Exception $e) {

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }



    public function xml2array ( $xmlObject, $out = array () )
    {
        foreach ((array) $xmlObject as $index => $node) {
            $out[$index] = ( is_object ( $node ) ) ?  $this->xml2array($node) : $node;
        }
        return $out;
    }

    function XMLtoArray($xml) {
        $previous_value = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->loadXml($xml);
        libxml_use_internal_errors($previous_value);
        if (libxml_get_errors()) {
            return [];
        }
        return $this->DOMtoArray($dom);
    }

    public function DOMtoArray($root) {
        $result = array();

        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if (in_array($child->nodeType,[XML_TEXT_NODE,XML_CDATA_SECTION_NODE])) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1
                        ? $result['_value']
                        : $result;
                }

            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->DOMtoArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->DOMtoArray($child);
                }
            }
        }
        return $result;
    }

    public function import(PurchaseImportRequest $request)
    {
        try
        {
            $model = $request->all();
            $supplier =  Person::whereType('suppliers')->where('number', $model['supplier_ruc'])->first();
            if(!$supplier)
            {
                return [
                    'success' => false,
                    'data' => 'Supplier not exist.'
                ];
            }
            $model['supplier_id'] = $supplier->id;
            $company = Company::active();
            $values = [
                'user_id' => auth()->id(),
                'external_id' => Str::uuid()->toString(),
                'supplier' => PersonInput::set($model['supplier_id']),
                'soap_type_id' => $company['soap_type_id'],
                'group_id' => ($model['document_type_id'] === '01') ? '01':'02',
                'state_type_id' => '01'
            ];

            $data = array_merge($model, $values);

            $purchase = DB::connection('tenant')->transaction(function () use ($data) {
                $doc = Purchase::create($data);
                foreach ($data['items'] as $row)
                {
                    $doc->items()->create($row);
                }

                $doc->purchase_payments()->create([
                    'date_of_payment' => $data['date_of_issue'],
                    'payment_method_type_id' => $data['payment_method_type_id'],
                    'payment' => $data['total'],
                ]);

                return $doc;
            });

            return [
                'success' => true,
                'message' => 'Xml cargado correctamente.',
                'data' => [
                    'id' => $purchase->id,
                ],
            ];



        }catch(Exception $e)
        {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

    }


    public function getPersons($type){

        $persons = Person::whereType($type)->orderBy('name')->take(20)->get()->transform(function($row) {
            return [
                'id' => $row->id,
                'description' => $row->number.' - '.$row->name,
                'name' => $row->name,
                'number' => $row->number,
                'identity_document_type_id' => $row->identity_document_type_id,
            ];
        });

        return $persons;

    }

    public function pdf($id)
    {
        $document = $this->record($id);
        $document['establishment'] = Establishment::where('id', $document->establishment_id)->first();
        // dd($document);
        $company = Company::active();

        $pdf = PDF::loadView('tenant.purchases.pdf', compact("document", "company"));
        $filename = 'COMPRA_'.$document->series.$document->number;

        return $pdf->stream($filename.'.pdf');
    }

    /**
     * Búsqueda optimizada de items para el select
     */
    public function searchItems(Request $request)
    {
        $query = $request->get('q', '');
        $itemId = $request->get('item_id', null);
        $limit = $request->get('limit', 50);

        // Si buscamos por ID específico
        if ($itemId) {
            $items = Item::whereNotIsSet()
                ->whereIsActive()
                ->where('id', $itemId)
                ->get();
        } else if (empty($query)) {
            // Si no hay query, devolver los primeros items ordenados por nombre
            $items = Item::whereNotIsSet()
                ->whereIsActive()
                ->orderBy('name')
                ->limit($limit)
                ->get();
        } else {
            // Búsqueda por texto
            $items = Item::whereNotIsSet()
                ->whereIsActive()
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('internal_id', 'like', '%' . $query . '%')
                      ->orWhere('description', 'like', '%' . $query . '%');
                })
                ->orderBy('name')
                ->limit($limit)
                ->get();
        }

        $transformedItems = collect($items)->transform(function($row) {
            $full_description = ($row->internal_id) ? $row->internal_id . ' - ' . $row->name : $row->name;
            return [
                'id' => $row->id,
                'item_code' => $row->internal_id, // Usar internal_id en lugar de item_code
                'name' => $row->name,
                'description' => $row->description,
                'full_description' => $full_description,
                'currency_type_id' => $row->currency_type_id,
                'currency_type_symbol' => $row->currency_type->symbol,
                'sale_unit_price' => $row->sale_unit_price,
                'purchase_unit_price' => $row->purchase_unit_price,
                'unit_type_id' => $row->unit_type_id,
                'purchase_tax_id' => $row->purchase_tax_id,
                'purchase_affectation_igv_type_id' => $row->purchase_affectation_igv_type_id,
                'has_perception' => (bool) $row->has_perception,
                'lots_enabled' => (bool) $row->lots_enabled,
                'percentage_perception' => $row->percentage_perception,
                'item_unit_types' => collect($row->item_unit_types)->transform(function($row) {
                    return [
                        'id' => $row->id,
                        'description' => "{$row->description}",
                        'item_id' => $row->item_id,
                        'unit_type_id' => $row->unit_type_id,
                        'unit_type' => $row->unit_type,
                        'quantity_unit' => $row->quantity_unit,
                        'price1' => $row->price1,
                        'price2' => $row->price2,
                        'price3' => $row->price3,
                        'price_default' => $row->price_default,
                    ];
                }),
                'series_enabled' => (bool) $row->series_enabled,
                'unit_type' => $row->unit_type,
                'warehouses' => $row->warehouses,
                'lots' => $row->lots
            ];
        });

        return response()->json(['items' => $transformedItems]);
    }

    /**
     * Leer XML desde la DIAN
     */
    public function readXMLFromDian(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|size:96|regex:/^[a-zA-Z0-9]+$/'
        ], [
            'identifier.size' => 'El identificador debe tener exactamente 96 caracteres.',
            'identifier.regex' => 'El identificador solo puede contener letras y números.'
        ]);

        try {
            $identifier = $request->input('identifier');

            // Obtener configuración del servicio de facturación
            $base_url = config('tenant.service_fact');
            $company = \Modules\Factcolombia1\Models\TenantService\Company::select('api_token')->firstOrFail();

            // Construir la URL del endpoint
            $endpoint_url = "{$base_url}ubl2.1/xml/document/{$identifier}";

            // Inicializar cURL
            $ch = curl_init($endpoint_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ]);

            // Ejecutar la petición
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            \Log::debug($endpoint_url);
            \Log::debug($company->api_token);
            \Log::debug($response);
            if ($response === false) {
                throw new \Exception('Error al conectar con el servidor de la DIAN');
            }

            $response_data = json_decode($response, true);

            if ($http_code !== 200 || !$response_data) {
                throw new \Exception('Respuesta inválida del servidor de la DIAN');
            }

            // Verificar si la consulta fue exitosa
            if (!$response_data['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $response_data['message'] ?? 'Documento no encontrado en la DIAN',
                    'dian_response' => $response_data['ResponseDian'] ?? null
                ]);
            }

            // Extraer y decodificar el XML
            if (!isset($response_data['ResponseDian']['Envelope']['Body']['GetXmlByDocumentKeyResponse']['GetXmlByDocumentKeyResult']['XmlBytesBase64'])) {
                throw new \Exception('XML no encontrado en la respuesta de la DIAN');
            }

            $xml_base64 = $response_data['ResponseDian']['Envelope']['Body']['GetXmlByDocumentKeyResponse']['GetXmlByDocumentKeyResult']['XmlBytesBase64'];
            $xml_content = base64_decode($xml_base64);

            if (!$xml_content) {
                throw new \Exception('Error al decodificar el XML de la DIAN');
            }

            // Procesar el XML para extraer los datos de la compra
            $purchase_data = $this->processXMLFromDian($xml_content);

            return response()->json([
                'success' => true,
                'message' => 'XML leído exitosamente desde la DIAN',
                'data' => [
                    'purchase_data' => $purchase_data,
                    'xml_content' => $xml_content,
                    'certificate_days_left' => $response_data['certificate_days_left'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar XML de la DIAN para extraer datos de compra
     */
    private function processXMLFromDian($xml_content)
    {
        try {
            // Cargar el XML
            $xml_document = new \DOMDocument();
            $xml_document->loadXML($xml_content);

            // Crear XPath para navegar el XML
            $xpath = new \DOMXPath($xml_document);

            // Registrar namespaces comunes
            $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
            $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
            $xpath->registerNamespace('sts', 'urn:dian:gov:co:facturaelectronica:Structures-2-1');

            // Extraer Serie de las extensiones DIAN usando el namespace correcto sts
            $series = null;
            $series_paths = [
                '//ext:UBLExtensions//ext:UBLExtension//ext:ExtensionContent//sts:DianExtensions//sts:InvoiceControl//sts:AuthorizedInvoices//sts:Prefix',
                '//sts:DianExtensions//sts:InvoiceControl//sts:AuthorizedInvoices//sts:Prefix',
                '//sts:InvoiceControl//sts:AuthorizedInvoices//sts:Prefix',
                '//sts:AuthorizedInvoices//sts:Prefix',
                '//sts:Prefix'
            ];

            foreach ($series_paths as $path) {
                $series = $this->getXmlValue($xpath, $path);
                if ($series) {
                    \Log::info("Serie encontrada usando ruta: {$path} = {$series}");
                    break;
                }
            }

            // Si no se encuentra en extensiones, intentar extraer de los primeros caracteres del número
            if (!$series) {
                $full_number = $this->getXmlValue($xpath, '//cbc:ID');
                if ($full_number) {
                    // Para facturas colombianas, la serie suele estar en los primeros caracteres antes de los números
                    if (preg_match('/^([A-Za-z]+)(\d+)$/', $full_number, $matches)) {
                        $series = $matches[1];
                    }
                }
            }

            // Extraer número completo y quitar la serie para obtener solo el número
            $full_number = $this->getXmlValue($xpath, '//cbc:ID');
            $number_only = $full_number;

            // Si existe una serie, removerla del número completo
            if ($series && $full_number) {
                // Si el número completo empieza con la serie, removerla
                if (strpos($full_number, $series) === 0) {
                    $number_only = substr($full_number, strlen($series));
                }
            } elseif ($full_number) {
                // Si no hay serie pero tenemos número completo, intentar extraer con regex
                if (preg_match('/^([A-Za-z]+)(\d+)$/', $full_number, $matches)) {
                    $series = $matches[1]; // Actualizar serie si se extrajo por regex
                    $number_only = $matches[2];
                    \Log::info("Serie y número extraídos por regex - Serie: {$series}, Número: {$number_only}");
                }
            }

            \Log::info("Extracción final - Número completo: {$full_number}, Serie: {$series}, Número solo: {$number_only}");

            // Extraer fechas específicas
            $issue_date = $this->getXmlValue($xpath, '//cbc:IssueDate');
            $due_date_paths = [
                '//cac:PaymentMeans//cbc:PaymentDueDate',
                '//cac:PaymentTerms//cbc:DueDate',
                '//cbc:PaymentDueDate',
                '//cbc:DueDate'
            ];

            $due_date = null;
            foreach ($due_date_paths as $path) {
                $due_date = $this->getXmlValue($xpath, $path);
                if ($due_date) {
                    \Log::info("Fecha de vencimiento encontrada usando ruta: {$path} = {$due_date}");
                    break;
                }
            }

            if (!$due_date) {
                \Log::warning("No se pudo encontrar la fecha de vencimiento en el XML");
            }

            \Log::info("Fecha de emisión: {$issue_date}, Fecha de vencimiento: {$due_date}");

            // Extraer datos básicos del documento
            $purchase_data = [
                'series' => $series,
                'number' => $number_only,
                'full_number' => $full_number,
                'issue_date' => $issue_date,
                'due_date' => $due_date,
                'issue_time' => $this->getXmlValue($xpath, '//cbc:IssueTime'),
                'document_currency_code' => $this->getXmlValue($xpath, '//cbc:DocumentCurrencyCode'),
                'note' => $this->getXmlValue($xpath, '//cbc:Note'),

                // Datos del proveedor
                'supplier' => [
                    'identification_number' => $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cac:Party//cac:PartyIdentification//cbc:ID') ??
                                              $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cbc:CompanyID'),
                    'name' => $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cac:Party//cac:PartyLegalEntity//cbc:RegistrationName') ??
                             $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cbc:RegistrationName'),
                    'address' => $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cac:Party//cac:PhysicalLocation//cac:Address//cbc:Line') ??
                                $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cac:PhysicalLocation//cac:Address//cbc:Line'),
                    'city' => $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cac:Party//cac:PhysicalLocation//cac:Address//cbc:CityName') ??
                             $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cac:PhysicalLocation//cac:Address//cbc:CityName'),
                    'country' => $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cac:Party//cac:PhysicalLocation//cac:Address//cac:Country//cbc:IdentificationCode') ??
                                $this->getXmlValue($xpath, '//cac:AccountingSupplierParty//cac:PhysicalLocation//cac:Address//cac:Country//cbc:IdentificationCode'),
                ],

                // Totales monetarios
                'monetary_totals' => [
                    'line_extension_amount' => $this->getXmlValue($xpath, '//cac:LegalMonetaryTotal//cbc:LineExtensionAmount'),
                    'tax_exclusive_amount' => $this->getXmlValue($xpath, '//cac:LegalMonetaryTotal//cbc:TaxExclusiveAmount'),
                    'tax_inclusive_amount' => $this->getXmlValue($xpath, '//cac:LegalMonetaryTotal//cbc:TaxInclusiveAmount'),
                    'payable_amount' => $this->getXmlValue($xpath, '//cac:LegalMonetaryTotal//cbc:PayableAmount'),
                    'allowance_total_amount' => $this->getXmlValue($xpath, '//cac:LegalMonetaryTotal//cbc:AllowanceTotalAmount'),
                ],

                // Items del documento
                'items' => $this->extractItemsFromXml($xpath),

                // Impuestos
                'tax_totals' => $this->extractTaxTotalsFromXml($xpath),
            ];

            // Log detallado de todos los campos extraídos
            \Log::info("Resumen de datos extraídos del XML:", [
                'serie' => $purchase_data['series'],
                'numero_completo' => $purchase_data['full_number'],
                'numero_solo' => $purchase_data['number'],
                'fecha_emision' => $purchase_data['issue_date'],
                'fecha_vencimiento' => $purchase_data['due_date'],
                'proveedor_nit' => $purchase_data['supplier']['identification_number'],
                'proveedor_nombre' => $purchase_data['supplier']['name'],
                'total_items' => count($purchase_data['items']),
                'monto_total' => $purchase_data['monetary_totals']['payable_amount'],
            ]);

            return $purchase_data;

        } catch (\Exception $e) {
            throw new \Exception('Error al procesar el XML: ' . $e->getMessage());
        }
    }

    /**
     * Obtener valor de un nodo XML usando XPath
     */
    private function getXmlValue($xpath, $query, $default = null, $context_node = null)
    {
        if ($context_node) {
            $nodes = $xpath->query($query, $context_node);
        } else {
            $nodes = $xpath->query($query);
        }
        return ($nodes->length > 0) ? trim($nodes->item(0)->nodeValue) : $default;
    }

    /**
     * Extraer items del XML
     */
    private function extractItemsFromXml($xpath)
    {
        $items = [];
        $item_nodes = $xpath->query('//cac:InvoiceLine');

        foreach ($item_nodes as $item_node) {
            $xpath_item = new \DOMXPath($item_node->ownerDocument);
            $xpath_item->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $xpath_item->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

            $xmlTaxId = $this->getXmlValue($xpath_item, './/cac:TaxTotal//cac:TaxSubtotal//cac:TaxCategory//cac:TaxScheme//cbc:ID', '', $item_node);
            $xmlTaxName = $this->getXmlValue($xpath_item, './/cac:TaxTotal//cac:TaxSubtotal//cac:TaxCategory//cac:TaxScheme//cbc:Name', '', $item_node);

            // Obtener descripción para logging
            $itemDescription = $this->getXmlValue($xpath_item, './/cac:Item//cbc:Description', '', $item_node);

            // Extraer información de descuentos (AllowanceCharge con ChargeIndicator=false)
            $allowanceAmount = $this->getXmlValue($xpath_item, './/cac:AllowanceCharge[cbc:ChargeIndicator="false"]//cbc:Amount', 0, $item_node);
            $allowanceBaseAmount = $this->getXmlValue($xpath_item, './/cac:AllowanceCharge[cbc:ChargeIndicator="false"]//cbc:BaseAmount', 0, $item_node);
            $allowanceMultiplier = $this->getXmlValue($xpath_item, './/cac:AllowanceCharge[cbc:ChargeIndicator="false"]//cbc:MultiplierFactorNumeric', 0, $item_node);
            $allowanceReason = $this->getXmlValue($xpath_item, './/cac:AllowanceCharge[cbc:ChargeIndicator="false"]//cbc:AllowanceChargeReason', '', $item_node);

            // Solo buscar descuentos si realmente existen elementos AllowanceCharge con ChargeIndicator=false
            // NO usar rutas alternativas que puedan confundir cargos con descuentos

            // Log para debugging
            if ($allowanceAmount > 0 || $allowanceMultiplier > 0) {
                \Log::info("Descuento extraído para '{$itemDescription}': Amount={$allowanceAmount}, Multiplier={$allowanceMultiplier}, Reason='{$allowanceReason}'");
            }

            // Log para debugging de precios
            $priceAmount = $this->getXmlValue($xpath_item, './/cac:Price//cbc:PriceAmount', 0, $item_node);
            $priceAmountAlt = $this->getXmlValue($xpath_item, './/cac:PricingReference//cac:AlternativeConditionPrice//cbc:PriceAmount', 0, $item_node);
            $priceAmountBase = $this->getXmlValue($xpath_item, './/cac:Price//cbc:BaseQuantity', 0, $item_node);
            $lineExtension = $this->getXmlValue($xpath_item, './/cbc:LineExtensionAmount', 0, $item_node);
            $quantity = $this->getXmlValue($xpath_item, './/cbc:InvoicedQuantity', 1, $item_node);
            \Log::info("Precios para '{$itemDescription}': PriceAmount={$priceAmount}, PriceAmountAlt={$priceAmountAlt}, BaseQuantity={$priceAmountBase}, LineExtension={$lineExtension}, Quantity={$quantity}");

            $items[] = [
                'id' => $this->getXmlValue($xpath_item, './/cbc:ID', null, $item_node),
                'quantity' => $this->getXmlValue($xpath_item, './/cbc:InvoicedQuantity', 0, $item_node),
                'unit_code' => $this->getXmlValueAttribute($xpath_item, './/cbc:InvoicedQuantity', 'unitCode', $item_node),
                'line_extension_amount' => $this->getXmlValue($xpath_item, './/cbc:LineExtensionAmount', 0, $item_node),
                'price_amount' => $this->getXmlValue($xpath_item, './/cac:Price//cbc:PriceAmount', 0, $item_node),
                'price_amount_alt' => $this->getXmlValue($xpath_item, './/cac:PricingReference//cac:AlternativeConditionPrice//cbc:PriceAmount', 0, $item_node),
                'item_description' => $itemDescription,
                'sellers_item_identification' => $this->getXmlValue($xpath_item, './/cac:Item//cac:SellersItemIdentification//cbc:ID', '', $item_node),
                // Extraer información de impuestos de la línea
                'tax_id_xml' => $xmlTaxId,
                'tax_name_xml' => $xmlTaxName,
                'tax_id_mapped' => $this->mapTaxIdFromXml($xmlTaxId, $xmlTaxName),
                'tax_percent' => $this->getXmlValue($xpath_item, './/cac:TaxTotal//cac:TaxSubtotal//cac:TaxCategory//cbc:Percent', 0, $item_node),
                'tax_amount' => $this->getXmlValue($xpath_item, './/cac:TaxTotal//cac:TaxSubtotal//cbc:TaxAmount', 0, $item_node),
                // Extraer información de descuentos de la línea
                'discount_amount' => $allowanceAmount,
                'discount_base_amount' => $allowanceBaseAmount,
                'discount_percentage' => floatval($allowanceMultiplier) > 0 ? floatval($allowanceMultiplier) : 0, // NO multiplicar por 100, usar el valor tal como viene
                'discount_reason' => $allowanceReason,
                // Solo marcar has_discount si realmente hay descuentos significativos
                'has_discount' => (floatval($allowanceAmount) > 0.01) || (floatval($allowanceMultiplier) > 0.001),
            ];
        }

        return $items;
    }

    /**
     * Extraer totales de impuestos del XML
     */
    private function extractTaxTotalsFromXml($xpath)
    {
        $tax_totals = [];
        $tax_nodes = $xpath->query('//cac:TaxTotal//cac:TaxSubtotal');

        foreach ($tax_nodes as $tax_node) {
            $xpath_tax = new \DOMXPath($tax_node->ownerDocument);
            $xpath_tax->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $xpath_tax->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

            $tax_totals[] = [
                'taxable_amount' => $this->getXmlValue($xpath_tax, './/cbc:TaxableAmount', 0, $tax_node),
                'tax_amount' => $this->getXmlValue($xpath_tax, './/cbc:TaxAmount', 0, $tax_node),
                'tax_id' => $this->getXmlValue($xpath_tax, './/cac:TaxCategory//cac:TaxScheme//cbc:ID', '', $tax_node),
                'tax_name' => $this->getXmlValue($xpath_tax, './/cac:TaxCategory//cac:TaxScheme//cbc:Name', '', $tax_node),
                'percent' => $this->getXmlValue($xpath_tax, './/cac:TaxCategory//cbc:Percent', 0, $tax_node),
            ];
        }

        return $tax_totals;
    }

    /**
     * Mapear tax_id del XML DIAN a tax_id de la base de datos
     */
    private function mapTaxIdFromXml($xmlTaxId, $xmlTaxName)
    {
        // Mapeo de códigos de impuestos del XML DIAN a IDs de la base de datos
        $taxMap = [
            '01' => 1, // IVA -> ID 1
            '02' => 2, // IC (Impuesto al Consumo) -> ID 2
            '03' => 3, // ICA -> ID 3
            '04' => 4, // INC -> ID 4
            '05' => 5, // ReteIVA -> ID 5
            '06' => 6, // ReteFuente -> ID 6
            '07' => 7, // ReteICA -> ID 7
            '20' => 8, // FtoHorticultura -> ID 8
            '21' => 9, // Timbre -> ID 9
            '22' => 10, // Bolsas -> ID 10
        ];

        // Si existe mapeo directo por código XML, usarlo
        if (isset($taxMap[$xmlTaxId])) {
            return $taxMap[$xmlTaxId];
        }

        // Si no, intentar mapear por nombre
        if (strpos(strtolower($xmlTaxName), 'iva') !== false) {
            return 1; // IVA por defecto
        }

        // Si no se puede mapear, devolver IVA por defecto
        return 1;
    }

    /**
     * Obtener valor de atributo de un nodo XML usando XPath
     */
    private function getXmlValueAttribute($xpath, $query, $attribute, $context_node = null)
    {
        if ($context_node) {
            $nodes = $xpath->query($query, $context_node);
        } else {
            $nodes = $xpath->query($query);
        }

        if ($nodes->length > 0 && $nodes->item(0)->hasAttribute($attribute)) {
            return $nodes->item(0)->getAttribute($attribute);
        }

        return null;
    }

}
