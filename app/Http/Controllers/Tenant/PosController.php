<?php

namespace App\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Item;
use App\Models\Tenant\Person;
use App\Models\Tenant\Catalogs\AffectationIgvType;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Table;
use App\Models\Tenant\TableAccount;
use App\Models\Tenant\Series;
use App\Models\Tenant\PaymentMethodType;
use App\Models\Tenant\CardBrand;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\Models\Tenant\User;
use Modules\Inventory\Models\Warehouse;
use App\Models\Tenant\Cash;
use App\Models\Tenant\Configuration;
use Modules\Inventory\Models\InventoryConfiguration;
use Modules\Inventory\Models\ItemWarehouse;
use Exception;
use Modules\Item\Models\Category;
use Modules\Finance\Traits\FinanceTrait;
use App\Models\Tenant\Company;
use App\Models\Tenant\Document;
use Barryvdh\DomPDF\Facade as PDF;
use Modules\Factcolombia1\Models\Tenant\{
    Currency,
    TypeDocument,
    Tax,
    PaymentMethod,
    PaymentForm,
    TypeInvoice,
};
use Carbon\Carbon;
use App\Models\Tenant\ConfigurationPos;
use App\Http\Requests\Tenant\ConfigurationPosRequest;
use Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration;
use App\Http\Resources\Tenant\PosCollection;
use Illuminate\Support\Facades\Auth;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceCompany
};

class PosController extends Controller
{

    use FinanceTrait;

    public function index()
    {
        $cash = Cash::where([['user_id', auth()->user()->id],['state', true]])->first();

        if(!$cash) return redirect()->route('tenant.cash.index');

        if(!$cash->resolution_id) return redirect()->route('tenant.cash.index');

        /*$configuration_pos_document = ConfigurationPos::first();
        if(!$configuration_pos_document) return redirect()->route('tenant.pos.configuration');*/

        $configuration = Configuration::first();
        $configuration_pos = ConfigurationPos::where('id', $cash->resolution_id)->firstOrFail();
//        \Log::debug($configuration_pos);
        $configuration->configuration_pos = $configuration_pos;

        $establishment_id = User::where('id',auth()->user()->id)->first();
        $tables = Establishment::select('tables')->where('id',$establishment_id->establishment_id)->first();
        $tables_quantity = $tables->tables;
        $cuentas = [];

        if(!empty($tables)){

            $tables_array = Table::select('id', 'table_number')->where('establishment_id', $establishment_id->establishment_id)->get();

            foreach($tables_array as $line){
                $account = TableAccount::select('account')
                ->where('account', $line->id)->whereIn('state', ['A', 'R'])->first();

                if(!empty($account)){
                    $cuentas[] = [
                        'id' => $account->account,
                        'state' => 1,
                        'table_number' => $line->table_number
                    ];
                }else{
                    $cuentas[] = [
                        'id' => $line->id,
                        'state' => 0,
                        'table_number' => $line->table_number
                    ];
                }
            }
        }

        $company = Company::select('soap_type_id')->first();
        $soap_company  = $company->soap_type_id;

        return view('tenant.pos.index', compact('configuration', 'soap_company', 'tables_quantity', 'cuentas'));
    }

    public function configuration()
    {
        $configuration = ConfigurationPos::first();
        return view('tenant.pos.configuration', compact('configuration'));
    }

    public function add_account(Request $request)
    {
        $user = Auth::user()->id;
        $data = $request->all();

        $table = Table::select('id')->where('establishment_id', $data['establecimiento'])
        ->where('table_number', $data['mesa'])->first();

        if (!$table) {
            return response()->json(['success' => false, 'message' => 'Mesa no encontrada'], 404);
        }

        $registro = new TableAccount();
        $registro->account = $table->id;
        $registro->state = 'A';
        $registro->price = $data['precio'];
        $registro->quantity = $data['cantidad'];
        $registro->item_id = $data['id'];
        $registro->item_description = $data['nombre'];
        $registro->created_at = Carbon::now();
        $registro->updated_at = Carbon::now();
        $registro->user_id = $user;
        $registro->save();

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado exitosamente',
            'data' => $registro
        ]);
    }

    public function account_list(Request $request){
        $data = $request->all();

        $table = Table::select('id')->where('establishment_id', $data['establecimiento'])
        ->where('table_number', $data['mesa'])
        ->where('id', $data['mesaId'])->first();

        $products = TableAccount::select('item_description', 'price', 'quantity', 'account', 'id', 'item_id', 'state')
        ->where('account', $table->id)->whereIn('state', ['A', 'R'])->get();

        if($products->isEmpty()){
            return response()->json([
                'message' => 'La cuenta no tiene productos.',
            ], 422);
        }else{
            return response()->json([
                'data' => $products
            ]);
        }
    }

    public function record_detalle(Request $request){

        $mesaId = $request->input('mesaId');
        $establecimiento = $request->input('establecimiento');
        $customerId = $request->input('customer');
        $total_venta = 0;
        $subtotal = 0;
        $descuento = 0;
        $total_sin_impuestos = 0;
        $impuesto = [];
        $total_impuestos = 0;

        $items = TableAccount::select('item_description', 'price', 'quantity', 'account', 'id', 'item_id', 'state')
        ->where('account', $mesaId)->whereIn('state', ['A', 'R'])->get();

        $sucursal = Establishment::where('id', $establecimiento)->first();

        $customer = Person::where('id', $customerId)->first();

        $company = Company::active();
        $date_of_issue = Carbon::now()->toDateString();
        $created_at = Carbon::now()->format('H:i:s');

        foreach ($items as $product) {
            $total_unidad = 0;
            $total_linea = 0;
            $total_linea_impuesto = 0;

            $item = Item::find($product->item_id);
            $taxes = Tax::select('id','rate', 'name', 'is_retention')->where('id', $item->tax_id)->first();

            if ($item && $taxes) {
                $total_unidad = ($item->sale_unit_price * $taxes->rate) / 100;
                $total_linea = $total_unidad * $product->quantity;
                $total_linea_impuesto = ($total_unidad + $item->sale_unit_price) * $product->quantity;

                if (!isset($impuesto[$taxes->id])) {
                    $impuesto[$taxes->id] = [
                        'name' => $taxes->name,
                        'total' => 0,
                        'is_retention' => $tax->is_retention ?? false,
                    ];
                }

                $impuesto[$taxes->id]['total'] += $total_linea;

                $product->item = $item;
                $product->total_tax = $total_linea;
                $product->subtotal = $total_linea_impuesto;
            }
            $subtotal += $item->sale_unit_price * $product->quantity;
            $total_impuestos += $total_linea;
        }

        $total_sin_impuestos = $subtotal - $descuento;
        $total_venta += $subtotal + $total_impuestos;

        if($items->isEmpty()){
            return response()->json([
                'message' => 'La cuenta no tiene productos.',
            ], 422);
        }

        $customPaper = [0, 0, 226, 600];
        $pdf = PDF::loadView('tenant.pos.account_ticket', compact('items', 'sucursal', 'customer', 'company', 'date_of_issue', 'created_at', 'subtotal', 'descuento', 'total_sin_impuestos', 'impuesto', 'total_venta'))
            ->setPaper($customPaper, 'portrait');
        return $pdf->stream("ticket.pdf");
    }

    public function transfer_account(Request $request){
        $data = $request->all();

        if($data['mesa_nueva'] <= 0){
            return response()->json([
                'message' => 'El número de la cuenta debe ser mayor de 0.',
            ], 422);
        }

        $table = Table::select('id')->where('establishment_id', $data['establecimiento'])
        ->where('table_number', $data['mesa_nueva'])->first();

        $products = TableAccount::select('item_description', 'price', 'quantity')
        ->where('account', $data['mesa_id'])->whereIn('state', ['A', 'R'])->get();

        if($products->isEmpty()){
            return response()->json([
                'message' => 'La cuenta ' . $data['mesa'] . ' no tiene productos.',
            ], 422);
        }else{

            TableAccount::where('account', $data['mesa_id'])
                ->whereIn('state', ['A', 'R'])
                ->update([
                'account' => $table->id,
            ]);

            return [
                'success' => true,
                'message' => 'Cuenta Trasladada Correctamente.',
            ];
        }

        $products2 = TableAccount::select('item_description', 'price', 'quantity')
        ->where('account', $data['mesa_id'])->where('state', 'R')->get();

        if(!$products2->isEmpty()){
            return response()->json([
                'message' => 'La cuenta ' . $data['mesa'] . ' se encuentra en proceso de facturación.',
            ], 422);
        }

    }

    public function delete_product(Request $request){
        $data = $request->all();

        TableAccount::where('account', $data['mesa_id'])
            ->where('state', 'A')
            ->where('id',  $data['cuenta_id'])
            ->delete();

        return [
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ];
    }

    public function shopping_car (Request $request){
        $data = $request->all();

        $table = Table::select('id')->where('establishment_id', $data['establecimiento'])
        ->where('table_number', $data['mesa'])->first();

        $products = TableAccount::select('item_description', 'price', 'quantity', 'account', 'id')
        ->where('account', $table->id)->whereIn('state', ['A', 'R'])->get();

        if($products->isEmpty()){
            return response()->json([
                'message' => 'La cuenta no tiene productos.',
            ], 422);
        }else{
            return response()->json([
                'data' => $products
            ]);
        }
    }

    public function delete_account(Request $request){
        $data = $request->all();

        TableAccount::where('account', $data['mesa_id'])
            ->whereIn('state', ['A', 'R'])
            ->delete();

        return [
            'success' => true,
            'message' => 'Cuenta Eliminada Correctamente.',
        ];
    }

    public function get_item($id, $id_cuenta){

        $item = Item::with('tax')->findOrFail($id);

        $tax_percentage = $item->tax ? $item->tax->percentage : 0;

        $item->sale_unit_price_with_tax = round($item->sale_unit_price * (1 + ($tax_percentage / 100)), 2);
        $id_user = auth()->user()->id;

        $profile = User::select('type')->where('id', $id_user)->first();

        if($profile->type == 'comand'){
            return response()->json([
                'message' => 'El usuario no puede facturar.',
            ], 422);
        }else{
            TableAccount::where('id', $id_cuenta)
                ->update([
                'state' => 'R',
            ]);

            return response()->json([
                'data' => $item
            ]);
        }

    }

    public function actualizar_estado_item($item) {
        $updated = TableAccount::where('id', $item)
            ->update(['state' => 'A']);

        return response()->json([
            'success' => $updated ? true : false
        ]);
    }

    public function records()
    {
        return [
            'data' => ConfigurationPos::all()
        ];
    }

    public function configuration_store(ConfigurationPosRequest $request)
    {
        try{
            $configuration = ConfigurationPos::updateOrCreate(['resolution_number' => $request->resolution_number, 'prefix' => $request->prefix], $request->all());
//            \Log::debug($request->all());
            if($request->electronic === true){
                $company = ServiceCompany::firstOrFail();
                $base_url = config("tenant.service_fact", "");
                $ch3 = curl_init("{$base_url}ubl2.1/config/resolution");
                $data = [
                    "delete_all_type_resolutions" => false,
                    "type_document_id" => 15,
                    "prefix" => $request->prefix,
                    "resolution" => $request->resolution_number,
                    "resolution_date" => Carbon::parse($request->resolution_date)->toDateString(),
                    "from" => $request->from,
                    "to" => $request->to,
                    'date_from' => Carbon::parse($request->date_from)->toDateString(),
                    'date_to' => Carbon::parse($request->date_end)->toDateString(),
                ];
                if($request->type_resolution == "Factura Electronica de Venta")
                    $data['type_document_id'] = 1;

                $data_resolution = json_encode($data);
                curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch3, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch3, CURLOPT_POSTFIELDS,($data_resolution));
                curl_setopt($ch3, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch3, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    "Authorization: Bearer {$company->api_token}"
                ));

                $response_resolution = curl_exec($ch3);
                $err = curl_error($ch3);
                curl_close($ch3);
                $respuesta = json_decode($response_resolution);

                //return json_encode($respuesta);

                if($err) {
                    $r = ConfigurationPos::where('resolution_number', $request->resolution_number)->where('prefix', $request->prefix)->first();
                    $r->forceDelete();
                    return [
                        'success' => false,
                        'message' => "Error en peticion Api Resolution.",
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Cambios guardados correctamente.',
            ];
        }
        catch (\Exception $e){
            $r = ConfigurationPos::where('resolution_number', $request->resolution_number)->where('prefix', $request->prefix)->first();
            $r->forceDelete();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function index_full()
    {
        $cash = Cash::where([['user_id', auth()->user()->id],['state', true]])->first();

        if(!$cash) return redirect()->route('tenant.cash.index');

        return view('tenant.pos.index_full');
    }

    public function search_items(Request $request)
    {
        $configuration =  Configuration::first();
        $items = Item::where('name','like',  '%' . $request->input_item . '%')
                    ->orWhere('description','like',  '%' . $request->input_item . '%')
                    ->orWhere('internal_id','like',  '%' . $request->input_item . '%')
                    ->orWhereHas('category', function($query) use($request) {
                        $query->where('name', 'like', '%' . $request->input_item . '%');
                    })
                    ->orWhereHas('brand', function($query) use($request) {
                        $query->where('name', 'like', '%' . $request->input_item . '%');
                    })
                    ->whereWarehouse()
                    ->whereIsActive()
                    ->when($request->has('cat') && $request->cat != '', function ($query) use ($request) {
                        $query->where('category_id', $request->cat);
                    })
                    ->paginate(50);
        return new PosCollection($items, $configuration);
    }

    public function tables()
    {

        $customers = $this->table('customers');
        $user = User::findOrFail(auth()->user()->id);

        $items = $this->table('items');

        $categories = Category::all();

        $currencies = Currency::where('id', 170)->get();
        $taxes = $this->table('taxes');
        $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();


        return compact('items', 'customers','currencies','taxes','user', 'categories', 'establishment');

    }

    public function payment_tables(){

        $payment_method_types = PaymentMethodType::all();
        $cards_brand = CardBrand::all();
        $payment_destinations = $this->getPaymentDestinations();

        $type_invoices = TypeInvoice::where('id', 1)->get();

        $type_documents = TypeDocument::query()
                            ->where('id', 1)
                            ->get()
                            ->each(function($typeDocument) {
                                $typeDocument->alert_range = (($typeDocument->to - 100) < (Document::query()
                                    ->hasPrefix($typeDocument->prefix)
                                    ->whereBetween('number', [$typeDocument->from, $typeDocument->to])
                                    ->max('number') ?? $typeDocument->from));

                                $typeDocument->alert_date = ($typeDocument->resolution_date_end == null) ? false : Carbon::parse($typeDocument->resolution_date_end)->subMonth(1)->lt(Carbon::now());
                            });

        $payment_methods = PaymentMethod::all();

        $payment_forms = PaymentForm::all();

        $series = Series::whereIn('document_type_id',['80'])
                        ->where([['establishment_id', auth()->user()->establishment_id],['contingency',false]])
                        ->get();

        $limit_uvt = AdvancedConfiguration::getPublicConfiguration(['uvt'])->getLimitUvt();
        return compact('payment_method_types','cards_brand', 'payment_destinations', 'series', 'type_invoices', 'type_documents', 'payment_methods', 'payment_forms', 'limit_uvt');
    }

    public function table($table)
    {

        if ($table === 'taxes') {

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
        }

        if ($table === 'customers') {
            $customers = Person::whereType('customers')->whereIsEnabled()->orderBy('name')->get()->transform(function($row) {
                return [
                    'id' => $row->id,
                    'description' => $row->number.' - '.$row->name,
                    'name' => $row->name,
                    'number' => $row->number,
                    'identity_document_type_id' => $row->identity_document_type_id,
                    'address' =>  $row->address,
                    'email' =>  $row->email,
                    'telephone' =>  $row->telephone,
                ];
            });
            return $customers;
        }

        if ($table === 'items') {

            $configuration =  Configuration::first();

            $items = Item::whereWarehouse()->whereNotItemsAiu()->whereIsActive()->where('unit_type_id', '!=', 'ZZ')->orderBy('description')->take(100)
                            ->get()->transform(function($row) use ($configuration) {
                                $full_description = ($row->internal_id)?$row->internal_id.' - '.$row->description:$row->name;
                                return [
                                    'id' => $row->id,
                                    'item_id' => $row->id,
                                    'full_description' => $full_description,
                                    'name' => $row->name,
                                    'description' => $row->description,
                                    'currency_type_id' => $row->currency_type->id,
                                    'internal_id' => $row->internal_id,
                                    'currency_type_symbol' => $row->currency_type->symbol,
                                    'sale_unit_price' => number_format($row->sale_unit_price, $configuration->decimal_quantity, ".",""),
                                    'unit_type_id' => $row->unit_type_id,
                                    'calculate_quantity' => (bool) $row->calculate_quantity,
                                    'tax_id' => $row->tax_id,
                                    'is_set' => (bool) $row->is_set,
                                    'edit_unit_price' => false,
                                    'aux_quantity' => 1,
                                    'edit_sale_unit_price' => number_format($row->sale_unit_price, $configuration->decimal_quantity, ".",""),
                                    'aux_sale_unit_price' => number_format($row->sale_unit_price, $configuration->decimal_quantity, ".",""),
                                    'image_url' => ($row->image !== 'imagen-no-disponible.jpg') ? asset('storage'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR.$row->image) : asset("/logo/{$row->image}"),
                                    'warehouses' => collect($row->warehouses)->transform(function($row) {
                                        return [
                                            'warehouse_description' => $row->warehouse->description,
                                            'stock' => $row->stock,
                                        ];
                                    }),
                                    'category_id' => ($row->category) ? $row->category->id : null,
                                    'sets' => collect($row->sets)->transform(function($r){
                                        return [
                                            $r->individual_item->name
                                        ];
                                    }),
                                    'unit_type' => $row->unit_type,
                                    'tax' => $row->tax,
                                    'item_unit_types' => $row->item_unit_types->transform(function($row) { return $row->getSearchRowResource();}),
                                    //'sale_unit_price_calculate' => self::calculateSalePrice($row)
                                    'sale_unit_price_with_tax' => $this->getSaleUnitPriceWithTax($row, $configuration->decimal_quantity)
                                ];
                            });
            return $items;
        }


        if ($table === 'card_brands') {

            $card_brands = CardBrand::all();
            return $card_brands;

        }

        return [];
    }

    /**
     * Retorna el precio de venta mas impuesto asignado al producto
     *
     * @param  Item $item
     * @param  $decimal_quantity
     * @return double
     */

     private static $advancedConfig = null;

     private function getAdvancedConfiguration()
     {
         // Si aún no se ha obtenido la configuración avanzada, la obtenemos.
         if (self::$advancedConfig === null) {
             self::$advancedConfig = \Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration::getPublicConfiguration();
         }
         return self::$advancedConfig;
     }

     private function getSaleUnitPriceWithTax($item, $decimal_quantity)
     {
         // Obtenemos la configuración avanzada (se consulta solo una vez gracias al cache estático).
         $advancedConfig = $this->getAdvancedConfiguration();

         // Se utiliza el valor de item_tax_included de AdvancedConfiguration para determinar el cálculo.
         if ($advancedConfig->item_tax_included) {
             // Si Incluir impuesto al precio de registro falso sedebe dejar el producto con el iva incluido
             $taxRate   = $item->tax->rate ?? 0;
             $conversion = $item->tax->conversion ?? 1;
             $price = $item->sale_unit_price * (1 + ($taxRate / $conversion));
         } else {
             // Incluir impuesto al precio de registro se debe sumar el iva pero como el precio se esta sumando el iva se deja tal cual
             $price = $item->sale_unit_price;

         }

         return number_format($price, $decimal_quantity, ".", "");
     }

    public static function calculateSalePrice($item)
    {
        $total_tax = 0;

        if($item->tax)
        {
            if($item->tax->is_fixed_value)
            {
                $total_tax = ( $item->tax->rate * 1 - ($item->discount < $item->unit_price * 1 ? $item->discount : 0));
            }

            if($item->tax->is_percentage)
            {
                $total_tax = ( ($item->unit_price * 1 - ($item->discount < $item->unit_price * 1 ? $item->discount : 0)) * ($item->tax->rate / $item->tax->conversion));

            }

        }
        else{

        }
    }

    public function payment()
    {
        return view('tenant.pos.payment');
    }

    public function status_configuration(){

        $configuration = Configuration::first();

        return $configuration;
    }

    public function validate_stock($item_id, $quantity){

        $inventory_configuration = InventoryConfiguration::firstOrFail();
        $warehouse = Warehouse::where('establishment_id', auth()->user()->establishment_id)->first();
        $item_warehouse = ItemWarehouse::where([['item_id',$item_id], ['warehouse_id',$warehouse->id]])->first();
        $item = Item::findOrFail($item_id);

        if($item->is_set){

            $sets = $item->sets;

            foreach ($sets as $set) {

                $individual_item = $set->individual_item;
                $item_warehouse = ItemWarehouse::where([['item_id',$individual_item->id], ['warehouse_id',$warehouse->id]])->first();

                if(!$item_warehouse)
                    return [
                        'success' => false,
                        'message' => "El producto seleccionado no está disponible en su almacén!"
                    ];

                $stock = $item_warehouse->stock - $quantity;


                if($item_warehouse->item->unit_type_id !== 'ZZ'){
                    if (($inventory_configuration->stock_control) && ($stock < 0)){
                        return [
                            'success' => false,
                            'message' => "El producto {$item_warehouse->item->description} registrado en el conjunto {$item->description} no tiene suficiente stock!"
                        ];
                    }
                }
                // dd($individual_item);
            }



        }else{


            if(!$item_warehouse)
                return [
                    'success' => false,
                    'message' => "El producto seleccionado no está disponible en su almacén!"
                ];

            $stock = $item_warehouse->stock - $quantity;


            if($item_warehouse->item->unit_type_id !== 'ZZ'){
                if (($inventory_configuration->stock_control) && ($stock < 0)){
                    return [
                        'success' => false,
                        'message' => "El producto {$item_warehouse->item->description} no tiene suficiente stock!"
                    ];
                }
            }

        }



        return [
            'success' => true,
            'message' => ''
        ];


    }

}
