<?php

namespace App\Http\Controllers\Tenant;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Person;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\Models\Tenant\Catalogs\ChargeDiscountType;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\SaleNote;
use App\Models\Tenant\SaleNoteItem;
use App\CoreFacturalo\Requests\Inputs\Common\LegendInput;
use App\Models\Tenant\Item;
use App\Models\Tenant\Series;
use App\Http\Resources\Tenant\DocumentPosCollection;
use App\Http\Resources\Tenant\SaleNoteResource;
use App\Http\Resources\Tenant\SaleNoteResource2;
use App\Models\Tenant\Catalogs\AffectationIgvType;
use App\Models\Tenant\Catalogs\DocumentType;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Catalogs\PriceType;
use App\Models\Tenant\Catalogs\SystemIscType;
use App\Models\Tenant\Catalogs\AttributeType;
use Modules\Factcolombia1\Models\Tenant\Company as CoCompany;
use Modules\Factcolombia1\Models\Tenant\NoteConcept;
use Modules\Factcolombia1\Http\Requests\Tenant\DocumentPosRequest;
use App\Models\Tenant\Company;
use App\Models\Tenant\Dispatch;
use App\Http\Requests\Tenant\SaleNoteRequest;
// use App\Models\Tenant\Warehouse;
use Illuminate\Support\Str;
use App\CoreFacturalo\Requests\Inputs\Common\PersonInput;
use App\CoreFacturalo\Requests\Inputs\Common\EstablishmentInput;
use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\CoreFacturalo\Template;
use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use App\Models\Tenant\PaymentMethodType;
use App\Mail\Tenant\SaleNoteEmail;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Mail;
use Modules\Inventory\Models\Warehouse;
use Modules\Item\Models\ItemLot;
use App\Models\Tenant\ItemWarehouse;
use Modules\Finance\Traits\FinanceTrait;
use Modules\Item\Models\ItemLotsGroup;
use App\Models\Tenant\Configuration;
use Modules\Factcolombia1\Models\Tenant\{
    Currency,
    TypeDocument,
    Tax,
    PaymentMethod,
    PaymentForm,
    TypeInvoice,
};
use Modules\Factcolombia1\Models\Tenant\{
    Company as PosCompany,
};
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceTenantCompany
};
use App\Models\Tenant\Document;
use App\Models\Tenant\DocumentPos;
use App\Models\Tenant\DocumentPosItem;
use App\Models\Tenant\DocumentPosPayment;
use App\Models\Tenant\ConfigurationPos;
use App\Http\Resources\Tenant\DocumentPosResource;
use App\Models\Tenant\Cash;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;

class DocumentPosController extends Controller
{
    use StorageDocument, FinanceTrait;

    protected $sale_note;
    protected $company;
    protected $apply_change;

    public function index()
    {
        return view('tenant.pos.documents');
    }

    public function create($id = null)
    {
        return view('tenant.sale_notes.form', compact('id'));
    }

    public function create_refund($id)
    {
        return view('tenant.pos.refund', compact('id'));
    }

    public function columns()
    {
        return [
            'date_of_issue' => 'Fecha de emisión',
//            'number' => 'Número',
//            'customer' => 'Cliente',
        ];
    }

    public function columns2()
    {
        return [
            'series' => Series::whereIn('document_type_id', ['80'])->get(),
        ];
    }

    public function records(Request $request)
    {
        $records = DocumentPos::where($request->column, 'like', "%{$request->value}%")->latest('id');
        return new DocumentPosCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function credit_note($id){
        $note = DocumentPos::with(['items'])->findOrFail($id);
        $invoice = DocumentPos::with(['items'])->findOrFail($id);
        $command = "credito";
        return view('tenant.pos.note', compact('note', 'invoice', 'command'));
    }

    public function debit_note($id){
        $note = DocumentPos::with(['items'])->findOrFail($id);
        $invoice = DocumentPos::with(['items'])->findOrFail($id);
        $command = "debito";
        return view('tenant.pos.note', compact('note', 'invoice', 'command'));
    }

    public function note($id, $command = null) {
        $note = DocumentPos::with(['items'])->findOrFail($id);
        $invoice = DocumentPos::with(['items'])->findOrFail($id);
        return view('tenant.pos.note', compact('note', 'invoice', 'command'));
    }

    public function app_data(){
        $record = Company::firstOrFail();
        return $record->only([
            'app_name',
            'app_owner_name',
            'app_business_name',
        ]);
    }

    public function sendEmail($number, $client){
        $client = Client::find($client);

        $company = ServiceTenantCompany::firstOrFail();

        $send= (object)['number'=> $number, 'email'=> $client->email];
        $data_send = json_encode($send);

        $base_url = config('tenant.service_fact');
        $ch2 = curl_init("{$base_url}send_mail");
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch2, CURLOPT_POSTFIELDS,($data_send));
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));

        $response = curl_exec($ch2);
        $respuesta = json_decode($response);
        curl_close($ch2);

        if(property_exists($respuesta, 'success'))
        {
            return [
                'success' => $respuesta->success,
                'message' => $respuesta->message
            ];
        }
        else{

            return [
                'success' => false,
                'message' => 'No se puedo enviar el correo.'
            ];

        }
    }

    public function store_app_data(Request $request){
        $record = Company::firstOrFail();
        $record->fill($request->all());
        $record->save();
        return [
            'success' => true,
            'message' => 'Datos de la aplicación actualizados correctamente.',
        ];
    }

    public function searchCustomers(Request $request)
    {
        $customers = Person::where('number','like', "%{$request->input}%")
                            ->orWhere('name','like', "%{$request->input}%")
                            ->whereType('customers')->orderBy('name')
                            ->whereIsEnabled()
                            ->get()->transform(function($row) {
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
        return compact('customers');
    }

    public function tables(){
        $customers = $this->table('customers');
        $establishments = Establishment::where('id', auth()->user()->establishment_id)->get();
        $company = Company::active();
        $payment_method_types = PaymentMethodType::all();
        $type_documents = TypeDocument::query()
                            ->get()
                            ->each(function($typeDocument) {
                                $typeDocument->alert_range = (($typeDocument->to - 100) < (Document::query()
                                    ->hasPrefix($typeDocument->prefix)
                                    ->whereBetween('number', [$typeDocument->from, $typeDocument->to])
                                    ->max('number') ?? $typeDocument->from));
                                $typeDocument->alert_date = ($typeDocument->resolution_date_end == null) ? false : Carbon::parse($typeDocument->resolution_date_end)->subMonth(1)->lt(Carbon::now());
                                $typeDocument->name_description = "{$typeDocument->name} / {$typeDocument->prefix} / {$typeDocument->resolution_number} / {$typeDocument->from} / {$typeDocument->to} / {$typeDocument->resolution_date_end}";
                            });
        $series = collect(Series::all())->transform(function($row) {
            return [
                'id' => $row->id,
                'contingency' => (bool) $row->contingency,
                'document_type_id' => $row->document_type_id,
                'establishment_id' => $row->establishment_id,
                'number' => $row->number
            ];
        });
        $payment_methods = PaymentMethod::all();
        $payment_forms = PaymentForm::all();
        $type_invoices = TypeInvoice::all();
        $currencies = Currency::all();
        $resolutions = TypeDocument::select('id', 'prefix', 'code', 'resolution_number', 'from', 'to', 'description', 'resolution_date_end')->whereNotNull('resolution_number')->whereIn('code', [1, 2, 3])->where('resolution_date_end', '>', Carbon::now())->get();
        $fe_resolution_id = auth()->user()->fe_resolution_id;
        $nc_resolution_id = auth()->user()->nc_resolution_id;
        $nd_resolution_id = auth()->user()->nd_resolution_id;
        $payment_destinations = $this->getPaymentDestinations();
        $taxes = $this->table('taxes');
        return compact('customers', 'establishments','currencies', 'taxes','company','payment_method_types', 'series', 'payment_destinations', 'type_documents', 'resolutions', 'fe_resolution_id', 'nc_resolution_id', 'nd_resolution_id'  );
    }

    public function changed($id)
    {
        $sale_note = DocumentPos::find($id);
        $sale_note->changed = true;
        $sale_note->save();
    }


    public function item_tables()
    {
        $taxes = $this->table('taxes');
        $items = $this->table('items');
        $categories = [];

        return compact('items', 'categories', 'taxes');
    }


    public function record($id)
    {
        $record = new DocumentPosResource(DocumentPos::findOrFail($id));
        return $record;
    }


    public function record2($id)
    {
        $record = new SaleNoteResource2(DocumentPos::findOrFail($id));
        return $record;
    }

    public function store(Request $request)
    {
        DB::connection('tenant')->beginTransaction();
        try{
            $app_data = $this->app_data();
//        DB::connection('tenant')->transaction(function () use ($request) {
            $type_document_string = 'el Documento Ticket Papel Nro: ';
            $data = $this->mergeData($request);
//            \Log::debug($request->all());
//            \Log::debug($request->type_resolution);
//            \Log::debug(json_encode($data));
//            return "";
            $customer = Person::where('number', $data['customer']['number'])->where('type', 'customers')->firstOrFail();
            $tax_totals = [];
            $invoice_lines = [];
            $tax_exclusive_amount = 0;
            foreach($data['items'] as $row){
                $invoice_lines[] = [
                    'unit_measure_id' => $row['item']['unit_type']['code'],
                    'invoiced_quantity' => $row['quantity'],
                    'line_extension_amount' => (string)($row['total'] - $row['total_tax']),
                    'free_of_charge_indicator' => false,
                    'description' => !empty($row['item']['description']) ? $row['item']['description'] : (!empty($row['item']['name']) ? $row['item']['name'] : 'Sin descripción'),
                    'notes' => null,
                    'code' => $row['item']['internal_id'],
                    'type_item_identification_id' => 4,
//                    'price_amount' => $row['item']['edit_sale_unit_price'],
                    'price_amount' => $row['subtotal'] / $row['quantity'],
                    'base_quantity' => $row['quantity']
                ];
                if($row['item']['tax'] !== null){
                    $invoice_lines[count($invoice_lines) - 1]['tax_totals'] = [
                        [
                            'tax_id' => $row['item']['tax']['type_tax']['id'],
                            'tax_amount' => $row['total_tax'],
//                            'taxable_amount' => number_format((float)($row['item']['sale_unit_price'] * $row['quantity']), 2, '.', ''),
                            'taxable_amount' => number_format((float)($row['unit_price'] * $row['quantity']), 2, '.', ''),
                            'percent' => $row['item']['tax']['rate'],
                        ]
                    ];
                }
                if($row['item']['tax'] !== null){
                    $tax_id = $row['item']['tax']['type_tax']['id'];
                    $percent = $row['item']['tax']['rate'];
                    $tax_amount = $row['total_tax'];
//                    $taxable_amount = $row['item']['sale_unit_price'] * $row['quantity'];
                    $taxable_amount = $row['unit_price'] * $row['quantity'];
                    if (strpos($taxable_amount, '.') !== false){
                        // Si ya tiene dos decimales, no es necesario agregar más
                        $taxable_amount = number_format($taxable_amount, 2, '.', '');
                    } else {
                        // Si solo tiene un decimal, agregar un cero adicional
                        $taxable_amount = number_format($taxable_amount, 1, '.', '') . '0';
                    }

                    $found = false;
                    foreach($tax_totals as $key => $tax){
                        if ($tax['tax_id'] == $tax_id && $tax['percent'] == $percent) {
                            $tax_totals[$key]['tax_amount'] += $tax_amount;
                            $tax_totals[$key]['taxable_amount'] += $taxable_amount;
                            $found = true;
                            break;
                        }
                    }
                    if(!$found){
                        $tax_totals[] = [
                            'tax_id' => $tax_id,
                            'percent' => $percent,
                            'tax_amount' => $tax_amount,
                            'taxable_amount' => $taxable_amount,
                        ];
                    }
//                    if(isset($tax_totals[$tax_id][$percent])) {
//                        // Si ya existe, actualizar los valores
//                        $tax_totals[$tax_id][$percent]['tax_amount'] += $tax_amount;
//                        $tax_totals[$tax_id][$percent]['taxable_amount'] += $taxable_amount;
//                    }
//                    else {
//                    // Si no existe, agregar un nuevo elemento
//                        $tax_totals[] = [
//                            'tax_id' => $tax_id,
//                            'percent' => $percent,
//                            'tax_amount' => $tax_amount,
//                            'taxable_amount' => $taxable_amount,
//                        ];
//                    }
///                    $tax_exclusive_amount += $tax_totals[count($tax_totals) - 1]['taxable_amount'];
                    // Sumar taxable_amount de los tax_totals de cada línea
                    if(isset($invoice_lines[count($invoice_lines) - 1]['tax_totals'])) {
                        foreach($invoice_lines[count($invoice_lines) - 1]['tax_totals'] as $tax_total) {
                            $tax_exclusive_amount += floatval($tax_total['taxable_amount']);
                        }
                    }
                }
            }
            if(isset($data['allowance_charges'])){
                if(count($data['allowance_charges']) > 0)
                    $allowance_charges = $data['allowance_charges'][0]['amount'];
                else
                    $allowance_charges = "0.00";
            }
            else
                $allowance_charges = "0.00";
            $data_invoice_pos = [
                'number' => $data['number'],
                'type_document_id' => 15,
                'date' => $data['date_of_issue'],
	            'time' => $data['time_of_issue'],
                'postal_zone_code' => '411001',
                'resolution_number' => $data['resolution_number'],
                'prefix' => $data['prefix'],
                'notes' => null,
                'sendmail' => true,
                'sendmailtome' => true,
                'software_manufacturer' => [
                    'name' => $app_data['app_owner_name'] ?? config('tenant.app_owner_name'),
                    'business_name' => $app_data['app_business_name'] ?? config('tenant.app_business_name'),
                    'software_name' => $app_data['app_name'] ?? config('tenant.app_name'),
                ],
                'buyer_benefits' => [
                    'code' => $data['customer']['number'],
                    'name' => $data['customer']['name'],
                    'points' => "0",
                ],
                'cash_information' => [
                    'plate_number' => $data['plate_number'],
                    'location' => auth()->user()->establishment->address,
                    'cashier' => auth()->user()->name,
                    'cash_type' => $data['cash_type'],
                    'sales_code' => $data['prefix'],
                    'subtotal' => $data['sale'],
                ],
                'customer' => [
                    'identification_number' => $data['customer']['number'],
                    'dv' => $data['customer']['dv'],
                    'name' => $data['customer']['name'],
                    'phone' => $data['customer']['telephone'],
                    'address' => $data['customer']['address'],
                    'email' => $data['customer']['email'],
                    'merchant_registration' => "0000000-00",
                    'type_document_identification_id' => $customer->identity_document_type_id,
                    'type_organization_id' => $customer->type_person_id,
                    'type_liability_id' => $customer->type_obligation_id,
                    'municipality_id_fact' => $customer->city_id,
                    'type_regime_id' => $customer->type_regime_id,
                ],
                'payment_form' => [
                    'payment_form_id' => 1,
                    'payment_method_id' => 30,
                    'payment_due_date' => $data['date_of_issue'],
                    'duration_measure' => "0",
                ],
                'legal_monetary_totals' => [
                    'line_extension_amount' => $data['sale'],
                    'tax_exclusive_amount' => (string)$tax_exclusive_amount,
                    'tax_inclusive_amount' => (string)($data['total'] + $allowance_charges),
//                    'tax_inclusive_amount' => (string)($data['sale'] + $data['total_tax']),
                    'allowance_total_amount' => $allowance_charges,
                    'payable_amount' => (string)$data['total'],
                ],
                'tax_totals' => $tax_totals,
                'allowance_charges' => $data['allowance_charges'] ?? [],
                'invoice_lines' => $invoice_lines,
            ];
            if($request->type_resolution == 'Factura Electronica de Venta')
                $data_invoice_pos['type_document_id'] = 1;
//            \Log::debug(json_encode($data_invoice_pos));
//            return [
//                'success' => false,
//                'message' => "Abortando...",
//            ];
            // gestion DIAN
            if ($data['electronic'] === true && (!isset($data['sincronize']) || $data['sincronize'] !== true)) {
                $company = ServiceTenantCompany::firstOrFail();
                $data_invoice_pos['invoice_template'] = "3";
                $data_invoice_pos['template_token'] = password_hash($company->identification_number, PASSWORD_DEFAULT);
                if($request->type_resolution == 'Factura Electronica de Venta')
                    $id_test = $company->test_id;
                else
                    $id_test = $company->test_set_id_eqdocs;
//                \Log::debug(json_encode($company));
//                \Log::debug("id test: ".$id_test);
                $base_url = config('tenant.service_fact');
                if($request->type_resolution == 'Factura Electronica de Venta'){
                    if($company->type_environment_id == 2 && $id_test != 'no_test_set_id'){
                        $ch = curl_init("{$base_url}ubl2.1/invoice/{$id_test}");
//                        \Log::debug("{$base_url}ubl2.1/invoice/{$id_test}");
                    }
                    else{
                        $ch = curl_init("{$base_url}ubl2.1/invoice");
//                        \Log::debug("{$base_url}ubl2.1/invoice");
                    }
                }
                else{
                    if($company->eqdocs_type_environment_id == 2 && $id_test != 'no_test_set_id'){
                        $ch = curl_init("{$base_url}ubl2.1/eqdoc/{$id_test}");
//                        \Log::debug("{$base_url}ubl2.1/eqdoc/{$id_test}");
                    }
                    else{
                        $ch = curl_init("{$base_url}ubl2.1/eqdoc");
//                        \Log::debug("{$base_url}ubl2.1/eqdoc");
                    }
                }
//                \Log::debug(json_encode($data_invoice_pos));
//                return ['success' => false, 'message' => "Guardado en Log...", 'errors' => null, 'data' => ['id' => null,],];
                $data_document = json_encode($data_invoice_pos);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS,($data_document));
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    "Authorization: Bearer {$company->api_token}"
                ));
                $response = curl_exec($ch);
//                 \Log::debug($data);
//                 \Log::debug($company->eqdocs_type_environment_id);
//                 \Log::debug($id_test);
//                 \Log::debug("{$base_url}ubl2.1/eqdoc");
//                 \Log::debug($company->api_token);
//                 \Log::debug($data_document);
//                 \Log::debug($response);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $response_model = json_decode($response);
                $zip_key = null;
                $invoice_status_api = null;

                if($httpcode === 422) {
                    $firstError = reset($response_model->errors)[0];
                    return [
                        'success' => false,
                        'message' => "Error el Documento Equivalente POS Nro: {$data['series']}{$data['number']}, Errores: {$response_model->message}, ".$firstError,
                        'errors' => $response_model->errors,
                        'data' => [
                            'id' => null,
                        ],
                    ];
                }

                if($request->type_resolution == 'Factura Electronica de Venta'){
                    $company_eqdocs_type_environment_id_for_use = $company->type_environment_id;
                    $company_test_set_id_eqdocs_for_use = $company->test_id;
                    $type_document_string = 'la Factura Electronica de Venta Nro: ';
                }
                else{
                    $company_eqdocs_type_environment_id_for_use = $company->eqdocs_type_environment_id;
                    $company_test_set_id_eqdocs_for_use = $company->test_set_id_eqdocs;
                    $type_document_string = 'el Documento Equivalente POS Nro: ';
                }

                if($company_eqdocs_type_environment_id_for_use == 2 && $company_test_set_id_eqdocs_for_use != 'no_test_set_id'){
                    if(property_exists($response_model, 'urlinvoicepdf') && property_exists($response_model, 'urlinvoicexml')){
                        if(!is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey)){
                            if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success)){
                                if($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success == 'false'){
                                    return [
                                        'success' => false,
                                        'message' => $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->ProcessedMessage,
                                        'data' => [
                                            'id' => null,
                                        ],
                                    ];
                                }
                            }
                        }
                        else
                            if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                                $zip_key = $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
                    }
                    else{
                        return [
                            'success' => false,
                            'message' => "Error {$type_document_string}{$data['series']}{$data['number']}, Errores: ",
                            'data' => [
                                'id' => null,
                            ],
                        ];
                    }

                    $response_status = null;
                    //\Log::debug($zip_key);
                    if($zip_key){
                        sleep(6);
                        $ch2 = curl_init("{$base_url}ubl2.1/status/zip/{$zip_key}");
                        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);

                        if($request->type_resolution == 'Factura Electronica de Venta'){
                            if(file_exists(storage_path('sendmail.api')))
                                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(array("sendmail" => true, "is_payroll" => false, "is_eqdoc" => false)));
                            else
                                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(array("sendmail" => false, "is_payroll" => false, "is_eqdoc" => false)));
                        }
                        else{
                            if(file_exists(storage_path('sendmail.api')))
                                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(array("sendmail" => true, "is_payroll" => false, "is_eqdoc" => true)));
                            else
                                curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(array("sendmail" => false, "is_payroll" => false, "is_eqdoc" => true)));
                        }

                        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Accept: application/json',
                            "Authorization: Bearer {$company->api_token}"
                        ));
                        $response_status = curl_exec($ch2);
//                        \Log::debug($response_status);
                        curl_close($ch2);
                        $response_status_decoded = json_decode($response_status);
                        if(property_exists($response_status_decoded, 'ResponseDian')){
                            if($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid == "true"){
                                $data['ambient_id'] = $company_eqdocs_type_environment_id_for_use;
                                $data['request_api'] = $data_document;
                                $data['response_api'] = $response_status;
                                $data['cude'] = $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->XmlDocumentKey;
                                $data['qr'] = $response_model->QRStr;
                            }
                            else{
                                if(isset($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string)){
                                    if(is_array($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string)){
                                        $mensajeerror = implode(",", $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string);
                                    }
                                    else{
                                        $mensajeerror = $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->ErrorMessage->string;
                                    }
                                }
                                else{
                                    $mensajeerror = $response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->StatusDescription;
                                }

                                if($response_status_decoded->ResponseDian->Envelope->Body->GetStatusZipResponse->GetStatusZipResult->DianResponse->IsValid == 'false'){
                                    return [
                                        'success' => false,
                                        'message' => "Error {$type_document_string}{$data['series']}{$data['number']}, Errores: ".$mensajeerror,
                                        'data' => [
                                            'id' => null,
                                        ],
                                    ];
                                }
                            }
                        }
                        else{
                            $mensajeerror = $response_status_decoded->message;
                            return [
                                'success' => false,
                                'message' => "Error {$type_document_string}{$data['series']}{$data['number']} Errores: ".$mensajeerror,
                                'data' => [
                                    'id' => null,
                                ],
                            ];
                        }
                    }
                    else{
                        // \Log::debug("C");
                        return [
                            'success' => false,
                            'message' => "Error de ZipKey.",
                            'data' => [
                                'id' => null,
                            ],
                        ];
                    }
                }
                else{
                    if(property_exists($response_model, 'send_email_success')){
                        if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == "true"){
                            $data['ambient_id'] = $company_eqdocs_type_environment_id_for_use;
                            $data['request_api'] = $data_document;
                            $data['response_api'] = json_encode($response_model);
                            $data['cude'] = $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->XmlDocumentKey;
                            $data['qr'] = $response_model->QRStr;
                        }
                        else
                        {
                            if(is_array($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string))
                                $mensajeerror = implode(",", $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string);
                            else
                                $mensajeerror = $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string;
                            if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'false'){
                                // if($invoice_json == NULL) // no existe $invoice_json en este archivo
                                return [
                                    'success' => false,
                                    'message' => "Error al Validar {$type_document_string}{$data['series']}{$data['number']} Errores: ".$mensajeerror,
                                    'data' => [
                                        'id' => null,
                                    ],
                                ];
                            }
                        }
                    }
                    else{
                        return [
                            'success' => false,
                            'message' => "Error al Validar {$type_document_string}{$data['series']}{$data['number']} Errores: ".$response_model->message,
                            'data' => [
                                'id' => null,
                            ],
                        ];
                    }
                }
            }

            // fin gestion DIAN
            $this->sale_note =  DocumentPos::create($data);
            // $this->sale_note->payments()->delete();
            $this->deleteAllPayments($this->sale_note->payments);
            foreach($data['items'] as $row) {
                $item_id = isset($row['id']) ? $row['id'] : null;
                $sale_note_item = DocumentPosItem::firstOrNew(['id' => $item_id]);
                if(isset($row['item']['lots'])){
                    $row['item']['lots'] = isset($row['lots']) ? $row['lots']:$row['item']['lots'];
                }
                $sale_note_item->fill($row);
                $sale_note_item->document_pos_id = $this->sale_note->id;
                $sale_note_item->save();
                if(isset($row['lots'])){
                    foreach($row['lots'] as $lot) {
                        $record_lot = ItemLot::findOrFail($lot['id']);
                        $record_lot->has_sale = true;
                        $record_lot->update();
                    }
                }
                if(isset($row['IdLoteSelected']))
                {
                    $lot = ItemLotsGroup::find($row['IdLoteSelected']);
                    $lot->quantity = ($lot->quantity - $row['quantity']);
                    $lot->save();
                }
            }

            //pagos
            // foreach ($data['payments'] as $row) {
            //     $this->sale_note->payments()->create($row);
            // }
            $this->savePayments($this->sale_note, $data['payments']);
            $this->setFilename();
            $this->createPdf($this->sale_note, "ticket", $this->sale_note->filename);
//        });
        }catch(\Exception $e){
//            \Log::debug(json_encode([
//                'success' => false,
//                'message' => $e->getMessage(),
//                'line' => $e->getLine(),
//                'trace' => $e->getTrace(),
//            ]));
            DB::connection('tenant')->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }
        DB::connection('tenant')->commit();

        return [
            'success' => true,
            'message' => "{$type_document_string}{$data['series']}{$data['number']} Procesado Correctamente.",
            'data' => [
                'id' => $this->sale_note->id,
            ],
        ];
    }

    public function destroy_sale_note_item($id)
    {
        $item = DocumentPosItem::findOrFail($id);

        if(isset($item->item->lots)){

            foreach($item->item->lots as $lot) {
                // dd($lot->id);
                $record_lot = ItemLot::findOrFail($lot->id);
                $record_lot->has_sale = false;
                $record_lot->update();
            }

        }

        $item->delete();

        return [
            'success' => true,
            'message' => 'eliminado'
        ];
    }

    public function mergeData($inputs)
    {
        $this->company = Company::active();
        $config = ConfigurationPos::first();
        $user = auth()->user();
        $cash = Cash::where('state', 1)
                    ->where('user_id', $user->id)
                    ->first();
        $config =  $cash->resolution;
        if(!$config){
            throw new Exception('Resolución no establecida en caja chica actual.');
        }
        $number = null;
        $document = DocumentPos::select('id', 'number')
                                ->where('prefix', $config->prefix)
                                ->orderBy('id', 'desc')
                                ->first();
        $number_by_documents = ($document) ? (int)$document->number + 1 : 1;
        if($number_by_documents < $config['generated'])
            $number = $config['generated'];
        else
            $number = ($document) ? (int)$document->number + 1 : 1;
//\Log::debug($config);
        $allowanceCharges = $inputs['allowance_charges'] ?? [];
        $total_discount = collect($allowanceCharges)->sum(function ($charge) {
            return isset($charge['amount']) ? (float) $charge['amount'] : 0;
        });

        $values = [
            //'automatic_date_of_issue' => $automatic_date_of_issue,
            'user_id' => auth()->id(),
            'external_id' => Str::uuid()->toString(),
            'customer' => Person::with('typePerson', 'typeRegime', 'identity_document_type', 'country', 'department', 'city')->findOrFail($inputs['customer_id']),
            'establishment' => EstablishmentInput::set($inputs['establishment_id']),
            'soap_type_id' => $this->company->soap_type_id,
            'state_type_id' => '01',
            'series' => $config->prefix,
            'resolution_number' => $config->resolution_number,
            'plate_number' => $config->plate_number,
            'cash_type' => $config->cash_type,
            'number' => $number,
            'prefix' => $config->prefix,
            'total_tax' => $inputs['total_tax'],
            'electronic' => (bool)$config->electronic,
            'total_discount' => $total_discount,
        ];
        unset($inputs['series_id']);
        $inputs->merge($values);
        return $inputs->all();
    }

//    public function recreatePdf($sale_note_id)
//    {
//        $this->sale_note = SaleNote::find($sale_note_id);
//        $this->createPdf();
//    }

    private function setFilename(){
        $name = [$this->sale_note->series,$this->sale_note->number,date('Ymd')];
        $this->sale_note->filename = join('-', $name);
        $this->sale_note->save();
    }

    public function toPrint($external_id, $format) {
        $sale_note = DocumentPos::where('external_id', $external_id)->first();
        if (!$sale_note) throw new Exception("El código {$external_id} es inválido, no se encontro la nota de venta relacionada");
        $view = $this->reloadPDF($sale_note, $format, $sale_note->filename);
        if($format == 'html') {
            return $view;
        }
        $temp = tempnam(sys_get_temp_dir(), 'sale_note');
        file_put_contents($temp, $this->getStorage($sale_note->filename, 'sale_note'));
        return response()->file($temp);
    }

    private function reloadPDF($sale_note, $format, $filename) {
        return $this->createPdf($sale_note, $format, $filename);
    }

    public function createPdf($sale_note = null, $format_pdf = null, $filename = null) {
        ini_set("pcre.backtrack_limit", "5000000");
        $template = new Template();
        $pdf = new Mpdf();
        $this->company = CoCompany::active();
        $this->document = ($sale_note != null) ? $sale_note : $this->sale_note;
        $this->configuration = Configuration::first();
        $configuration = $this->configuration->formats;
        $base_template = $configuration;
        $path_css = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.
                                             DIRECTORY_SEPARATOR.'pdf'.
                                             DIRECTORY_SEPARATOR.$base_template.
                                             DIRECTORY_SEPARATOR.'style.css');
        $stylesheet = file_get_contents($path_css);
        if($format_pdf == 'html') {
            $html = $template->pdf($base_template, "document_pos", $this->company, $this->document, 'ticket');
            $html = str_replace(
                '</head>', // Buscar el cierre de la cabecera
                '<style>'.$stylesheet.'</style></head>', // Incrustar el CSS antes del cierre
                $html
            );
            return $html;
        }
        $html = $template->pdf($base_template, "document_pos", $this->company, $this->document, $format_pdf);
        if (($format_pdf === 'ticket') OR ($format_pdf === 'ticket_58')) {
            $width = ($format_pdf === 'ticket_58') ? 56 : 78 ;
            if(config('tenant.enabled_template_ticket_80')) $width = 76;

            $company_logo      = ($this->company->logo) ? 40 : 0;
            $company_name      = (strlen($this->company->name) / 20) * 10;
            $company_address   = (strlen($this->document->establishment->address) / 30) * 10;
            $company_number    = $this->document->establishment->telephone != '' ? '10' : '0';
            $customer_name     = strlen($this->document->customer->name) > '25' ? '10' : '0';
            $customer_address  = (strlen($this->document->customer->address) / 200) * 10;
            $p_order           = $this->document->purchase_order != '' ? '10' : '0';

            $total_exportation = $this->document->total_exportation != '' ? '10' : '0';
            $total_free        = $this->document->total_free != '' ? '10' : '0';
            $total_unaffected  = $this->document->total_unaffected != '' ? '10' : '0';
            $total_exonerated  = $this->document->total_exonerated != '' ? '10' : '0';
            $total_taxed       = $this->document->total_taxed != '' ? '10' : '0';
            $quantity_rows     = count($this->document->items);
            $payments     = $this->document->payments()->count() * 2;

            $extra_by_item_description = 0;
            $discount_global = 0;
            foreach ($this->document->items as $it) {
                if(strlen($it->item->description)>100){
                    $extra_by_item_description +=24;
                }
                if ($it->discounts) {
                    $discount_global = $discount_global + 1;
                }
            }
            $legends = $this->document->legends != '' ? '10' : '0';
            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    $width,
                    175 +
                    (($quantity_rows * 8) + $extra_by_item_description) +
                    ($discount_global * 3) +
                    $company_logo +
                    $payments +
                    $company_name +
                    $company_address +
                    $company_number +
                    $customer_name +
                    $customer_address +
                    $p_order +
                    $legends +
                    $total_exportation +
                    $total_free +
                    $total_unaffected +
                    $total_exonerated +
                    $total_taxed],
                'margin_top' => 0,
                'margin_right' => 2,
                'margin_bottom' => 0,
                'margin_left' => 2
            ]);
        } else if($format_pdf === 'a5'){
            $company_name      = (strlen($this->company->name) / 20) * 10;
            $company_address   = (strlen($this->document->establishment->address) / 30) * 10;
            $company_number    = $this->document->establishment->telephone != '' ? '10' : '0';
            $customer_name     = strlen($this->document->customer->name) > '25' ? '10' : '0';
            $customer_address  = (strlen($this->document->customer->address) / 200) * 10;
            $p_order           = $this->document->purchase_order != '' ? '10' : '0';
            $total_exportation = $this->document->total_exportation != '' ? '10' : '0';
            $total_free        = $this->document->total_free != '' ? '10' : '0';
            $total_unaffected  = $this->document->total_unaffected != '' ? '10' : '0';
            $total_exonerated  = $this->document->total_exonerated != '' ? '10' : '0';
            $total_taxed       = $this->document->total_taxed != '' ? '10' : '0';
            $quantity_rows     = count($this->document->items);
            $discount_global = 0;
            foreach ($this->document->items as $it) {
                if ($it->discounts) {
                    $discount_global = $discount_global + 1;
                }
            }
            $legends           = $this->document->legends != '' ? '10' : '0';

            $alto = ($quantity_rows * 8) +
                    ($discount_global * 3) +
                    $company_name +
                    $company_address +
                    $company_number +
                    $customer_name +
                    $customer_address +
                    $p_order +
                    $legends +
                    $total_exportation +
                    $total_free +
                    $total_unaffected +
                    $total_exonerated +
                    $total_taxed;
            $diferencia = 148 - (float)$alto;

            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    210,
                    $diferencia + $alto
                    ],
                'margin_top' => 2,
                'margin_right' => 5,
                'margin_bottom' => 0,
                'margin_left' => 5
            ]);
       } else {
            $pdf_font_regular = config('tenant.pdf_name_regular');
            $pdf_font_bold = config('tenant.pdf_name_bold');
            if ($pdf_font_regular != false) {
                $defaultConfig = (new ConfigVariables())->getDefaults();
                $fontDirs = $defaultConfig['fontDir'];
                $defaultFontConfig = (new FontVariables())->getDefaults();
                $fontData = $defaultFontConfig['fontdata'];
                $pdf = new Mpdf([
                    'fontDir' => array_merge($fontDirs, [
                        app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.
                                                DIRECTORY_SEPARATOR.'pdf'.
                                                DIRECTORY_SEPARATOR.$base_template.
                                                DIRECTORY_SEPARATOR.'font')
                    ]),
                    'fontdata' => $fontData + [
                        'custom_bold' => [
                            'R' => $pdf_font_bold.'.ttf',
                        ],
                        'custom_regular' => [
                            'R' => $pdf_font_regular.'.ttf',
                        ],
                    ]
                ]);
            }

        }

        $pdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);
        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        /*if(config('tenant.pdf_template_footer')) {
            $html_footer = $template->pdfFooter($base_template);
            $pdf->SetHTMLFooter($html_footer);
        }*/
        $this->uploadFile($this->document->filename, $pdf->output('', 'S'), 'sale_note');
    }

    public function uploadFile($filename, $file_content, $file_type){
        $this->uploadStorage($filename, $file_content, $file_type);
    }

    public function table($table){
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

            case 'customers':
                $customers = Person::whereType('customers')->whereIsEnabled()->orderBy('name')->take(20)->get()->transform(function($row) {
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
                break;

            case 'items':
                $establishment_id = auth()->user()->establishment_id;
                $warehouse = Warehouse::where('establishment_id', $establishment_id)->first();
                $warehouse_id = ($warehouse) ? $warehouse->id:null;
                $items_u = Item::whereWarehouse()->whereIsActive()->whereNotIsSet()->orderBy('description')->get();
                $items_s = Item::where('unit_type_id','ZZ')->whereIsActive()->orderBy('description')->get();
                $items = $items_u->merge($items_s);
                return collect($items)->transform(function($row) use($warehouse_id, $warehouse){
                    $detail = $this->getFullDescription($row, $warehouse);
                    return [
                        'id' => $row->id,
                        'internal_id' => $row->internal_id,
                        'name' => $row->name,
                        'description' => $row->description,
                        'full_description' => $detail['full_description'],
                        'brand' => $detail['brand'],
                        'category' => $detail['category'],
                        'stock' => $detail['stock'],
                        'currency_type_id' => $row->currency_type_id,
                        'currency_type_symbol' => $row->currency_type->symbol,
                        'sale_unit_price' => round($row->sale_unit_price, 2),
                        'purchase_unit_price' => $row->purchase_unit_price,
                        'unit_type_id' => $row->unit_type_id,
                        'tax_id' => $row->tax_id,
                        'lots_enabled' => (bool) $row->lots_enabled,
                        'series_enabled' => (bool) $row->series_enabled,
                        'is_set' => (bool) $row->is_set,
                        'warehouses' => collect($row->warehouses)->transform(function($row) {
                            return [
                                'warehouse_id' => $row->warehouse->id,
                                'warehouse_description' => $row->warehouse->description,
                                'stock' => $row->stock,
                            ];
                        }),
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
                        'lots' => $row->item_lots->where('has_sale', false)->where('warehouse_id', $warehouse_id)->transform(function($row) {
                            return [
                                'id' => $row->id,
                                'series' => $row->series,
                                'date' => $row->date,
                                'item_id' => $row->item_id,
                                'warehouse_id' => $row->warehouse_id,
                                'has_sale' => (bool)$row->has_sale,
                                'lot_code' => ($row->item_loteable_type) ? (isset($row->item_loteable->lot_code) ? $row->item_loteable->lot_code:null):null
                            ];
                        }),
                        'lots_group' => collect($row->lots_group)->transform(function($row){
                            return [
                                'id'  => $row->id,
                                'code' => $row->code,
                                'quantity' => $row->quantity,
                                'date_of_due' => $row->date_of_due,
                                'checked'  => false
                            ];
                        }),
                        'lot_code' => $row->lot_code,
                        'date_of_due' => $row->date_of_due,
                        'unit_type' => $row->unit_type,
                        'tax' => $row->tax,
                    ];
                });
                break;
            default:
                return [];
                break;
        }
    }


    public function getFullDescription($row, $warehouse){
        $desc = ($row->internal_id)?$row->internal_id.' - '.$row->name : $row->name;
        $category = ($row->category) ? "{$row->category->name}" : "";
        $brand = ($row->brand) ? "{$row->brand->name}" : "";
        if($row->unit_type_id != 'ZZ')
        {
            $warehouse_stock = ($row->warehouses && $warehouse) ? number_format($row->warehouses->where('warehouse_id', $warehouse->id)->first()->stock,2) : 0;
            $stock = ($row->warehouses && $warehouse) ? "{$warehouse_stock}" : "";
        }
        else{
            $stock = '';
        }
        $desc = "{$desc} - {$brand}";
        return [
            'full_description' => $desc,
            'brand' => $brand,
            'category' => $category,
            'stock' => $stock,
        ];
    }

    public function searchCustomerById($id){
        $customers = Person::whereType('customers')
                    ->where('id',$id)
                    ->get()->transform(function($row) {
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
        return compact('customers');
    }

    public function option_tables(){
        $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
        $series = Series::where('establishment_id',$establishment->id)->get();
        $type_documents = TypeDocument::query()
                            ->get()
                            ->each(function($typeDocument) {
                                $typeDocument->alert_range = (($typeDocument->to - 100) < (Document::query()
                                    ->hasPrefix($typeDocument->prefix)
                                    ->whereBetween('number', [$typeDocument->from, $typeDocument->to])
                                    ->max('number') ?? $typeDocument->from));
                                $typeDocument->alert_date = ($typeDocument->resolution_date_end == null) ? false : Carbon::parse($typeDocument->resolution_date_end)->subMonth(1)->lt(Carbon::now());
                            });
        return compact('series', 'type_documents');
    }

    public function email(Request $request){
        $company = Company::active();
        $record = DocumentPos::find($request->input('id'));
        $customer_email = $request->input('customer_email');
        Mail::to($customer_email)->send(new SaleNoteEmail($company, $record));
        return [
            'success' => true
        ];
    }

    public function dispatches(){
        $dispatches = Dispatch::latest()->get(['id','series','number'])->transform(function($row) {
            return [
                'id' => $row->id,
                'series' => $row->series,
                'number' => $row->number,
                'number_full' => "{$row->series}-{$row->number}",
            ];
        });
        return $dispatches;
    }

    public function enabledConcurrency(Request $request){
        $sale_note = DocumentPos::findOrFail($request->id);
        $sale_note->enabled_concurrency = $request->enabled_concurrency;
        $sale_note->update();
        return [
            'success' => true,
            'message' => ($sale_note->enabled_concurrency) ? 'Recurrencia activada':'Recurrencia desactivada'
        ];
    }

    public function getNextNumber($type_document_id, $prefix){
        $company = ServiceTenantCompany::firstOrFail();
        $base_url = config('tenant.service_fact');
        $ch = curl_init("{$base_url}ubl2.1/next-consecutive");
        $data = json_encode([
            'type_document_id' => $type_document_id,
            'prefix' => $prefix
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }

    public function setJsonAnulate($document) {
        $company = Company::active();
        $request_api = json_decode($document->request_api);
        if($request_api->type_document_id == 15)
            $document_type = TypeDocument::where('code', 26)->first();
        else
            $document_type = TypeDocument::where('code', 4)->first();

        $data_consecutive = $this->getNextNumber($document_type->code, $document_type->prefix);
        if($request_api->type_document_id == 15)
            $request_api->legal_monetary_totals->tax_exclusive_amount = $request_api->legal_monetary_totals->line_extension_amount;
        $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
        $json = [
            'prefix' => $document_type->prefix,
            'resolution_number' => $document_type->resolution_number,
            'number' => $data_consecutive->number,
            'date' => Carbon::now()->format('Y-m-d'),
            'time' => Carbon::now()->format('H:i:s'),
            'establishment_name' => $establishment->description,
            'establishment_address' => $establishment->address,
            'establishment_phone' => $establishment->telephone,
            'billing_reference' => [
                'number' => $request_api->prefix.$request_api->number,
                'issue_date' => $request_api->date,
                'type_document_id' => 15,
                "uuid" => $document->cude
            ],
            'is_eqdoc' => $request_api->type_document_id == 15 ? true : false,
            'discrepancyresponsecode' => 2,
            'notes' => $request_api->type_document_id == 15 ? 'NOTA CREDITO A DOCUMENTO EQUIVALENTE POS' : 'NOTA CREDITO A DOCUMENTO FACTURA ELECTRONICA POS',
            'type_document_id' => $request_api->type_document_id == 15 ? 26 : 4,
            'type_invoice_id' => $document_type->id,
            'type_resolution' => $document_type->name,
            'sendmail' => true,
            'sendmailtome' => true,
            'head_note' => '',
            'foot_note' => '',
            'customer' => $request_api->customer,
            'tax_totals' => $request_api->tax_totals,
            'legal_monetary_totals' => $request_api->legal_monetary_totals,
            'credit_note_lines' => $request_api->invoice_lines
        ];
        return $json;
    }

    public function sendJsonAnulate($json){
        $company = ServiceTenantCompany::firstOrFail();
        $base_url = config('tenant.service_fact');
        $ch = curl_init("{$base_url}ubl2.1/credit-note");
        $data = json_encode($json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response = curl_exec($ch);
        curl_close($ch);
//        \Log::debug($data);
//        \Log::debug($company->api_token);
//        \Log::debug("{$base_url}ubl2.1/credit-note");
//        \Log::debug($response);
        return json_decode($response);
    }

    public function storeNote(DocumentPosRequest $request) {
        DB::connection('tenant')->beginTransaction();
//        try {
            $note_service = $request->note_service;
            $url_name_note = '';
//            \Log::debug($request->all());
//            return "";
            $resolution = TypeDocument::where('id', $request['type_document_id'])->first();
            $type_document_service = $resolution['code'];
            if($type_document_service == 4 || $type_document_service == 26){
                $url_name_note = 'credit-note';
            }
            elseif($type_document_service == 5 || $type_document_service == 25){
                $url_name_note = 'debit-note';
            }
            $note_service['type_document_id'] = $type_document_service;
            $this->company = PosCompany::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();
            if (($this->company->limit_documents != 0) && (DocumentPos::count() >= $this->company->limit_documents))
                return [
                        'success' => false,
                        'message' => '"Has excedido el límite de documentos de tu cuenta."'
                ];
            // $correlative_api = $this->getCorrelativeInvoice($type_document_service);
            $company = ServiceTenantCompany::firstOrFail();
            // si la empresa esta en habilitacion, envio el parametro ignore_state_document_id en true
            // para buscar el correlativo en api sin filtrar por el campo state_document_id=1
            $ignore_state_document_id = ($company->type_environment_id === 2);
//            \Log::debug($request->all());
            $correlative_api = $this->getCorrelativeInvoice($type_document_service, $resolution['prefix'], $ignore_state_document_id);
            // dd($correlative_api);

            if(!is_numeric($correlative_api)){
                return [
                    'success' => false,
                    'message' => 'Error al obtener correlativo Api.'
                ];
            }

            if($request->type_eq_doc == 15){
                $note_service['is_eqdoc'] = true;
                $note_service['billing_reference']['type_document_id'] = 15;
            }
            $note_service['number'] = $correlative_api;
            $note_service['prefix'] = $resolution['prefix'];
            $note_service['resolution_number'] = $resolution['resolution_number'];
            $note_service['date'] = date('Y-m-d', strtotime($request->date_issue));
            $note_service['time'] = date('H:i:s');
            $datoscompany = PosCompany::with('type_regime', 'version_ubl', 'type_identity_document')->firstOrFail();
            // $company = ServiceTenantCompany::firstOrFail();
            $note_concept_id = NoteConcept::query()->where('id', $request->note_concept_id)->get();
            $note_service['discrepancyresponsecode'] = $note_concept_id[0]->code;
            $note_service['ivaresponsable'] = $datoscompany->type_regime->name;
            $note_service['nombretipodocid'] = $datoscompany->type_identity_document->name;
            $note_service['tarifaica'] = $datoscompany->ica_rate;
            $note_service['actividadeconomica'] = $datoscompany->economic_activity_code;
            $note_service['notes'] = $request->observation;
            $sucursal = \App\Models\Tenant\Establishment::where('id', auth()->user()->establishment_id)->first();
            if(file_exists(storage_path('sendmail.api')))
                $note_service['sendmail'] = true;
            $note_service['ivaresponsable'] = $datoscompany->type_regime->name;
            $note_service['establishment_name'] = $sucursal->description;
            if($sucursal->address != '-')
                $note_service['establishment_address'] = $sucursal->address;
            if($sucursal->telephone != '-')
                $note_service['establishment_phone'] = $sucursal->telephone;
            $note_service['establishment_email'] = $sucursal->email;

            if(in_array($note_service['customer']['type_document_identification_id'], [1, 2, 3, 6, 10]))
                $note_service['customer']['dv'] = $this->validarDigVerifDIAN($note_service['customer']['identification_number']);
            else{
                $city = City::where('id', $note_service['customer']['municipality_id_fact'])->first();
                $note_service['customer']['municipality_name'] = $city->name;
                $state = Department::where('id', $city->department_id)->first();
                $note_service['customer']['state_name'] = $state->name;
                $country = ServiceCountry::where('code', 'like', '%'.Country::where('id', $state->country_id)->first()->code.'%')->first();
//                \Log::debug($country);
                $note_service['customer']['country_id'] = $country->id;
                unset($note_service['customer']['municipality_id_fact']);
                unset($note_service['customer']['dv']);
            }

            $billing_reference_number = explode('-', $note_service['billing_reference']['number']);
            $document_source = DocumentPos::where('prefix', $billing_reference_number[0])->where('number', $billing_reference_number[1])->first();

            if($request->currency_id != 170)
                $note_service['currency_id'] = TypeCurrency::where('code', 'like', Currency::where('id', $request->currency_id)->first()['code'].'%')->first()['id'];
            else
                $note_service['currency_id'] = 35;

            if(!isset($note_service['calculationrate']))
                $note_service['calculationrate'] = $document_source->calculationrate ?? 1;
            $calculationRate = $note_service['calculationrate'] ?? 1;
            $data_document = json_encode($note_service);

            if($request->currency_id != 170){
                $note_service['k_supplement_national']['FctConvCop'] = $calculationRate ?? 1;
                $note_service['k_supplement_national']['MonedaCop'] = Currency::where('id', $request->currency_id)->first()['code'];
                $note_service['k_supplement_national']['SubTotalCop'] = $note_service['legal_monetary_totals']['line_extension_amount'];
                $note_service['k_supplement_national']['DescuentoDetalleCop'] = isset($note_service['legal_monetary_totals']['allowance_total_amount']) ? $note_service['legal_monetary_totals']['allowance_total_amount']: 0.00;
                $note_service['k_supplement_national']['TotalFacturaCop'] = $note_service['legal_monetary_totals']['payable_amount'];
                $note_service['k_supplement_national']['RecargoDetalleCop'] = isset($note_service['legal_monetary_totals']['charge_total_amount']) ? $note_service['legal_monetary_totals']['charge_total_amount'] : 0;
                $note_service['k_supplement_national']['TotalBrutoFacturaCop'] = $note_service['legal_monetary_totals']['tax_exclusive_amount'];
                $note_service['k_supplement_national']['TotIvaCop'] = number_format(array_sum(array_map(function($t) { return isset($t['tax_id']) && $t['tax_id'] == 1 ? ((float)$t['tax_amount']) : 0; }, $note_service['tax_totals'])), 2, '.', '');
                $note_service['k_supplement_national']['TotIncCop'] = number_format(array_sum(array_map(function($t) { return isset($t['tax_id']) && $t['tax_id'] == 4 ? ((float)$t['tax_amount']) : 0; }, $note_service['tax_totals'])), 2, '.', '');
                $note_service['k_supplement_national']['TotBolCop'] = number_format(array_sum(array_map(function($t) { return isset($t['tax_id']) && $t['tax_id'] == 10 ? ((float)$t['tax_amount']) : 0; }, $note_service['tax_totals'])), 2, '.', '');
                $note_service['k_supplement_national']['TotICLCop'] = number_format(array_sum(array_map(function($t) { return isset($t['tax_id']) && $t['tax_id'] == 19 ? ((float)$t['tax_amount']) : 0; }, $note_service['tax_totals'])), 2, '.', '');
                $note_service['k_supplement_national']['TotINPPCop'] = number_format(array_sum(array_map(function($t) { return isset($t['tax_id']) && $t['tax_id'] == 20 ? ((float)$t['tax_amount']) : 0; }, $note_service['tax_totals'])), 2, '.', '');
                $note_service['k_supplement_national']['TotIBUACop'] = number_format(array_sum(array_map(function($t) { return isset($t['tax_id']) && $t['tax_id'] == 21 ? ((float)$t['tax_amount']) : 0; }, $note_service['tax_totals'])), 2, '.', '');
                $note_service['k_supplement_national']['TotICUICop'] = number_format(array_sum(array_map(function($t) { return isset($t['tax_id']) && $t['tax_id'] == 22 ? ((float)$t['tax_amount']) : 0; }, $note_service['tax_totals'])), 2, '.', '');
                $note_service['k_supplement_national']['TotADVCop'] = number_format(array_sum(array_map(function($t) { return isset($t['tax_id']) && $t['tax_id'] == 23 ? ((float)$t['tax_amount']) : 0; }, $note_service['tax_totals'])), 2, '.', '');
                $note_service['k_supplement_national']['ImpOtroCop'] = number_format(array_sum(array_map(function($t) { return isset($t['tax_id']) && $t['tax_id'] == 15 ? ((float)$t['tax_amount']) : 0; }, $note_service['tax_totals'])), 2, '.', '');
                $note_service['k_supplement_national']['MntImpCop'] = "0.00";
                $note_service['k_supplement_national']['TotalNetoFacturaCop'] = $note_service['legal_monetary_totals']['payable_amount'];
                $note_service['k_supplement_national']['MntDctoCop'] = isset($note_service['legal_monetary_totals']['allowance_total_amount']) ? $note_service['legal_monetary_totals']['allowance_total_amount'] : 0.00;
                $note_service['k_supplement_national']['MntRcgoCop'] = isset($note_service['legal_monetary_totals']['charge_total_amount']) ? $note_service['legal_monetary_totals']['charge_total_amount'] : 0;
                $note_service['k_supplement_national']['VlrPagarCop'] = $note_service['legal_monetary_totals']['payable_amount'];
                $note_service['k_supplement_national']['ReteFueCop'] = "0.00";
                $note_service['k_supplement_national']['ReteIvaCop'] = "0.00";
                $note_service['k_supplement_national']['ReteIcaCop'] = "0.00";
                $note_service['k_supplement_national']['TotAnticiposCop'] = "0.00";
            }
            $data_document_foreign_currency = json_encode($this->multiplyMonetaryValues($note_service, $calculationRate));
            $note_service['foot_note'] = "Modo de operación: Software Propio - by ".env('APP_NAME', 'TORRE SOFTWARE');
            $id_test = $company->test_id;
            $base_url = config('tenant.service_fact');
            if($company->type_environment_id == 2 && $company->test_id != 'no_test_set_id')
                $ch = curl_init("{$base_url}ubl2.1/{$url_name_note}/{$id_test}");
            else
                $ch = curl_init("{$base_url}ubl2.1/{$url_name_note}");
            $data_document = json_encode($note_service);

//\Log::debug("{$base_url}ubl2.1/{$url_name_note}");
//\Log::debug($company->api_token);
//\Log::debug($correlative_api);
//\Log::debug($data_document);
//\Log::debug($data_document_foreign_currency);
//            return $data_document;
//            return "";
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            if($request->currency_id != 170)
                curl_setopt($ch, CURLOPT_POSTFIELDS,($data_document_foreign_currency));
            else
                curl_setopt($ch, CURLOPT_POSTFIELDS,($data_document));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));
            $response = curl_exec($ch);
            curl_close($ch);
//\Log::debug($response);
//return "";

            $response_model = json_decode($response);
            $zip_key = null;
            $invoice_status_api = null;
            $response_status = null;

            if($company->type_environment_id == 2 && $company->test_id != 'no_test_set_id'){
                if(array_key_exists('urlinvoicepdf', $response_model) && array_key_exists('urlinvoicexml', $response_model) )
                {
                    if(!is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                    {
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success))
                        {
                            if($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->Success == 'false')
                            {
                                return [
                                    'success' => false,
                                    'message' => $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ErrorMessageList->XmlParamsResponseTrackId->ProcessedMessage
                                ];
                            }
                        }
                    }
                    else
                    {
                        if(is_string($response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey))
                        {
                            $zip_key = $response_model->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
                        }
                    }
                }
                $response_status = null;
            }
            else{
                if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == "true")
                    $type_document_service = $type_document_service;
//                    $this->setStateDocument($type_document_service, $correlative_api);
                else
                {
                    if(is_array($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string))
                        $mensajeerror = implode(",", $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string);
                    else
                        $mensajeerror = $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->ErrorMessage->string;
                    if($response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == 'false')
                    {
                        return [
                            'success' => false,
                            'message' => "Error al Validar Nota Nro: {$correlative_api} Errores: ".$mensajeerror
                        ];
                    }
                }
            }

            $this->company = PosCompany::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();
            if (($this->company->limit_documents != 0) && (DocumentPos::count() >= $this->company->limit_documents)) throw new \Exception("Has excedido el límite de documentos de tu cuenta.");

            $data = [
                'customer_id' => $request->customer_id,
                'document_type_id' => null,
                'establishment_id' => $sucursal->id,
                'type_document_id' => $type_document_service,
                'currency_id' => $request->currency_id,
                'date_issue' => $note_service['date'],
                'date_of_issue' => $note_service['date'],
                'time_of_issue' => $note_service['time'],
                'exchange_rate_sale' => 1,
                'date_expiration' => null,
                'type_invoice_id' => $request->type_eq_doc,
                'total_discount' => $request->total_discount,
                'total_tax' => $request->total_tax,
                'watch' => false,
                'subtotal' => $request->subtotal,
                'items' => $request->items,
                'taxes' => $request->taxes,
                'total' => $request->total,
                'sale' => $request->sale,
                'time_days_credit' => 0,
                'service_invoice' => json_encode($note_service),
                'payment_form_id' => 1,
                'payment_method_id' => 1,
                'payments' => [],
                'electronic' => true,
                'type_resolution' => $resolution['name'],
                'prefix' => $note_service['prefix'],
                'paid' => 1,
                'user_id' => auth()->user()->id,
                'external_id' => Str::uuid()->toString(),
                'customer' => Person::where('id', $request->customer_id)->first(),
                'establishment' => $sucursal,
                'soap_type_id' => "01",
                'state_type_id' => "01",
                'series' => $note_service['prefix'],
                'resolution_number' => $note_service['resolution_number'],
                'plate_number' => null,
                'cash_type' => null,
                'number' => $note_service['number'],
            ];

            $this->sale_note = DocumentPos::create($data);
            $this->sale_note->request_api = json_encode($note_service);
            $this->sale_note->cude = $response_model->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->XmlDocumentKey;
            $this->sale_note->response_api = json_encode($response_model);
            $this->sale_note->qr = $response_model->QRStr;
            $this->sale_note->note_concept_id = $request->reference_id;
            $this->sale_note->save();
            $this->deleteAllPayments($this->sale_note->payments);
            $data['items'] = json_decode(json_encode($data['items']), true);
            foreach($data['items'] as $row) {
                $item_id = isset($row['id']) ? $row['id'] : null;
                $sale_note_item = DocumentPosItem::firstOrNew(['id' => $item_id]);
                if(isset($row['item']['lots'])){
                    $row['item']['lots'] = isset($row['lots']) ? $row['lots']:$row['item']['lots'];
                }
                $sale_note_item->fill($row);
                $sale_note_item->document_pos_id = $this->sale_note->id;
                $sale_note_item->refund = true;
                $sale_note_item->save();
                if(isset($row['lots'])){
                    foreach($row['lots'] as $lot) {
                        $record_lot = ItemLot::findOrFail($lot['id']);
                        $record_lot->has_sale = true;
                        $record_lot->update();
                    }
                }
                if(isset($row['IdLoteSelected'])){
                    $lot = ItemLotsGroup::find($row['IdLoteSelected']);
                    $lot->quantity = ($lot->quantity - $row['quantity']);
                    $lot->save();
                }
            }
            $this->setFilename();
/*        }
        catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            // Inicializar el mensaje de error
            $userFriendlyMessage = 'Ocurrió un error inesperado.';
            // Verificar si hay un mensaje de error específico en la respuesta de la API
            if (isset($response_model->message)) {
                $userFriendlyMessage = $response_model->message;  // Mensaje general de la API
                // Verificar si hay detalles de errores específicos
                if (isset($response_model->errors) && is_object($response_model->errors)) {
                    $errorDetailsArray = []; // Cambia a array para mejorar eficiencia
                    foreach ($response_model->errors as $field => $errorMessages) {
                        if (is_array($errorMessages)) {
                            $errorDetailsArray[] = implode(', ', $errorMessages);
                        } else {
                            $errorDetailsArray[] = $errorMessages;
                        }
                    }
                    // Concatenar detalles de los errores al mensaje para el usuario
                    if (!empty($errorDetailsArray)) {
                        $userFriendlyMessage .= ' ' . implode(' ', $errorDetailsArray);
                    }
                }
            }
            // Obtener el mensaje de la excepción
            $errorMessage = $e->getMessage();
            // Verificar si el mensaje contiene "Undefined property: stdClass::$Response"
            if (strpos($errorMessage, 'Undefined property: stdClass::$Response') !== false) {
                // Si el mensaje contiene "Undefined property: stdClass::$Response", no mostrar nada
                $errorMessage = '';
            }
            // Devolver la respuesta con un mensaje de error más detallado
            return [
                'success' => false,
                'validation_errors' => true,
                'message' =>  $errorMessage . ' ' . $userFriendlyMessage,
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }   */
        DB::connection('tenant')->commit();
        $this->company = PosCompany::query()->with('country', 'version_ubl', 'type_identity_document')->firstOrFail();
        if (($this->company->limit_documents != 0) && (DocumentPos::count() >= $this->company->limit_documents - 10))
            $over = ", ADVERTENCIA, ha consumido ".DocumentPos::count()." documentos de su cantidad contratada de: ".$this->company->limit_documents;
        else
            $over = "";
        return [
            'success' => true,
            'message' => "Se registro con éxito el documento #{$this->sale_note->prefix}{$this->sale_note->number}. {$over}",
            'data' => [
                'id' => $this->sale_note->id
            ]
           //'data' => $data_document
        ];
    }

    public function anulate($id){
        DB::connection('tenant')->beginTransaction();
        try {
            $obj = DocumentPos::find($id);
            if($obj->electronic){
                //enviar json
                $json = $this->setJsonAnulate($obj);
//                \Log::debug(json_encode($obj));
//                \Log::debug(json_encode($json));
//                return "";
                $response_api = $this->sendJsonAnulate($json);
                $response = json_decode(json_encode($response_api), FALSE);
                if(isset($response->errors)) {
                    return response([
                        'success' => false,
                        'message' => $response->message,
                        'errors' => $response->errors
                    ], 500);
                }
                if(isset($response->exception)){
                    return response([
                        'success' => false,
                        'message' => $response->message,
                    ], 500);
                }
                if(isset($response->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid)){
//                if(isset($response->success)) {
                    if($response->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == "false"){
//                    if(!$response->success) {
                        return response([
                            'success' => $response->success ?? false,
                            'message' => 'Rechazado: '.$response->message,
                        ], 500);
                    }
                    if($response->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->IsValid == "true"){
                        $obj->state_type_id = 11;
                        // guardar datos de nota de credito aqui
                        $obj->save();
                    }
                }
            }
            $obj->state_type_id = 11;
            // guardar datos de nota de credito aqui
            $obj->save();
            $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
            $warehouse = Warehouse::where('establishment_id',$establishment->id)->first();

            if($obj->electronic){
                $data = [
                    'customer_id' => $obj->customer_id,
                    'document_type_id' => null,
                    'establishment_id' => $obj->establishment_id,
                    'type_document_id' => $json['type_document_id'],
                    'currency_id' => $obj->currency_id,
                    'date_issue' => $json['date'],
                    'date_of_issue' => $json['date'],
                    'time_of_issue' => $json['time'],
                    'exchange_rate_sale' => $obj->exchange_rate_sale,
                    'date_expiration' => null,
                    'type_invoice_id' => $json['type_invoice_id'],
                    'total_discount' => $obj->total_discount,
                    'total_tax' => $obj->total_tax,
                    'watch' => false,
                    'subtotal' => $obj->subtotal,
                    'items' => json_decode($obj->items),
                    'taxes' => $obj->taxes,
                    'total' => $obj->total,
                    'sale' => $obj->sale,
                    'time_days_credit' => 0,
                    'service_invoice' => json_encode($json),
                    'payment_form_id' => 1,
                    'payment_method_id' => 1,
                    'payments' => [],
                    'electronic' => true,
                    'type_resolution' => $json['type_resolution'],
                    'prefix' => $json['prefix'],
                    'paid' => 1,
                    'user_id' => auth()->user()->id,
                    'external_id' => Str::uuid()->toString(),
                    'customer' => $obj->customer,
                    'establishment' => $obj->establishment,
                    'soap_type_id' => "01",
                    'state_type_id' => "01",
                    'series' => $json['prefix'],
                    'resolution_number' => $json['resolution_number'],
                    'plate_number' => null,
                    'cash_type' => null,
                    'number' => $json['number'],
                ];

                $this->sale_note = DocumentPos::create($data);
                $this->sale_note->request_api = json_encode($json);
                $this->sale_note->cude = $response->ResponseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult->XmlDocumentKey;
                $this->sale_note->response_api = json_encode($response);
                $this->sale_note->qr = $response->QRStr;
                $this->sale_note->note_concept_id = $obj->id;
                $this->sale_note->save();
                $this->deleteAllPayments($this->sale_note->payments);
                $data['items'] = json_decode(json_encode($data['items']), true);
                foreach($data['items'] as $row) {
                    $item_id = isset($row['id']) ? $row['id'] : null;
                    $sale_note_item = DocumentPosItem::firstOrNew(['id' => $item_id]);
                    if(isset($row['item']['lots'])){
                        $row['item']['lots'] = isset($row['lots']) ? $row['lots']:$row['item']['lots'];
                    }
                    $sale_note_item->fill($row);
                    $sale_note_item->document_pos_id = $this->sale_note->id;
                    $sale_note_item->refund = true;
                    $sale_note_item->save();
                    if(isset($row['lots'])){
                        foreach($row['lots'] as $lot) {
                            $record_lot = ItemLot::findOrFail($lot['id']);
                            $record_lot->has_sale = true;
                            $record_lot->update();
                        }
                    }
                    if(isset($row['IdLoteSelected'])){
                        $lot = ItemLotsGroup::find($row['IdLoteSelected']);
                        $lot->quantity = ($lot->quantity - $row['quantity']);
                        $lot->save();
                    }
                }
                $this->setFilename();
            }

            foreach ($obj->items as $item){
                $quantity = $item->quantity;
                if($item->refund == 1){
                    $quantity = -($item->quantity);
                }
                $item->document_pos->inventory_kardex()->create([
                    'date_of_issue' => date('Y-m-d'),
                    'item_id' => $item->item_id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $quantity //* ($item->refund ? -1 : 1),
                ]);
                $wr = ItemWarehouse::where([['item_id', $item->item_id],['warehouse_id', $warehouse->id]])->first();
                if($wr){
                    $wr->stock =  $wr->stock + $quantity; // * ($item->refund ? -1 : 1));
                    $wr->save();
                }
                $this->voidedLots($item);
            }
            DB::connection('tenant')->commit();
            return [
                'success' => true,
                'message' => 'N. Venta anulada con éxito'
            ];
        }catch (\Exception $e){
            DB::connection('tenant')->rollBack();
            \Log::debug($e->getMessage());
            \Log::debug($e->getLine());
            return response([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ], 500);
        }
        DB::connection('tenant')->commit();
    }

    public function anulateResolutions($id){
        $obj =  DocumentPos::find($id);
        $request_api = null;
        $records = [];
        if($obj->electronic){
            $request_api = json_decode($obj->request_api);
            if($request_api->type_document_id == 15)
                $records = TypeDocument::where('code', 26)->get();
            else
                $records = TypeDocument::where('code', 4)->get();
        }
        return [
            'data' => $obj->electronic ? $records : [],
            'quantity' => $obj->electronic ? $records->count() : 1
        ];
    }

    public function totals()
    {

        $records = DocumentPos::where([['state_type_id', '01']])->get();
        $total_pen = 0;
        $total_paid_pen = 0;
        $total_pending_paid_pen = 0;


        $total_pen = $records->sum('total');

        foreach ($records as $sale_note) {

            $total_paid_pen += $sale_note->payments->sum('payment');

        }

        $total_pending_paid_pen = $total_pen - $total_paid_pen;

        return [
            'total_pen' => number_format($total_pen, 2, ".", ""),
            'total_paid_pen' => number_format($total_paid_pen, 2, ".", ""),
            'total_pending_paid_pen' => number_format($total_pending_paid_pen, 2, ".", "")
        ];

    }

    public function downloadFile($filename){
        $company = ServiceTenantCompany::firstOrFail();
        $base_url = config('tenant.service_fact');
        $ch2 = curl_init("{$base_url}ubl2.1/download/{$company->identification_number}/{$filename}/BASE64");
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_data = curl_exec($ch2);
        $err = curl_error($ch2);
        curl_close($ch2);
        if($err){
            return [
                'success' => false,
                'message' => "No se pudo descargar el archivo: ".$filename
            ];
        }
        else{
            return $response_data;
        }
    }

    public function downloadExternal($external_id){
        $document = DocumentPos::where('external_id', $external_id)->first();
        $type_document_id = $document->electronic ? json_decode($document->request_api)->type_document_id : null;
        if($type_document_id == 4 || $type_document_id == 26){
            $company = ServiceTenantCompany::firstOrFail();
            $base_url = config('tenant.service_fact');
            $ch2 = curl_init("{$base_url}ubl2.1/download/{$company->identification_number}/NCS-{$document->prefix}{$document->number}.pdf");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch2, CURLOPT_POSTFIELDS, "");
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));
            $response = curl_exec($ch2);
            curl_close($ch2);
            $filename="NCS-{$document->prefix}{$document->number}.pdf";
            return response($response, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]);
        }
        else{
            $this->reloadPDF($document, 'ticket', null);
            return $this->downloadStorage($document->filename, 'sale_note');
        }
    }

    private function savePayments($sale_note, $payments){

        $total = $sale_note->total;
        $balance = $total - collect($payments)->sum('payment');

        $search_cash = ($balance < 0) ? collect($payments)->firstWhere('payment_method_type_id', '01') : null;

        $this->apply_change = false;

        if($balance < 0 && $search_cash){

            $payments = collect($payments)->map(function($row) use($balance){

                $change = null;
                $payment = $row['payment'];

                if($row['payment_method_type_id'] == '01' && !$this->apply_change){

                    $change = abs($balance);
                    $payment = $row['payment'] - abs($balance);
                    $this->apply_change = true;

                }

                return [
                    "id" => null,
                    "document_id" => null,
                    "document_pos_id" => null,
                    "date_of_payment" => $row['date_of_payment'],
                    "payment_method_type_id" => $row['payment_method_type_id'],
                    "reference" => $row['reference'],
                    "payment_destination_id" => isset($row['payment_destination_id']) ? $row['payment_destination_id'] : null,
                    "payment_filename" => isset($row['payment_filename']) ? $row['payment_filename'] : null,
                    "change" => $change,
                    "payment" => $payment
                ];

            });
        }

        // dd($payments, $balance, $this->apply_change);

        foreach ($payments as $row) {

            if($balance < 0 && !$this->apply_change){
                $row['change'] = abs($balance);
                $row['payment'] = $row['payment'] - abs($balance);
                $this->apply_change = true;
            }

            $record_payment = $sale_note->payments()->create($row);

            if(isset($row['payment_destination_id'])){
                $this->createGlobalPayment($record_payment, $row);
            }

            if(isset($row['payment_filename'])){
                $record_payment->payment_file()->create([
                    'filename' => $row['payment_filename']
                ]);
            }

        }
    }


    private function voidedLots($item){

        $i_lots_group = isset($item->item->lots_group) ? $item->item->lots_group:[];

        $lot_group_selected = collect($i_lots_group)->first(function($row){
            return $row->checked;
        });

        if($lot_group_selected){

            $lot = ItemLotsGroup::find($lot_group_selected->id);
            $lot->quantity =  $lot->quantity + $item->quantity;
            $lot->save();

        }

        if(isset($item->item->lots)){

            foreach ($item->item->lots as $it) {

                if($it->has_sale == true){

                    $ilt = ItemLot::find($it->id);
                    $ilt->has_sale = false;
                    $ilt->save();

                }

            }
        }

    }

    public function sincronize()
    {
        try {
            $company = ServiceTenantCompany::firstOrFail();
            $docController = new DocumentController();
            $docController->sincronize_resolutions($company->identification_number);
            $base_url = config('tenant.service_fact');

            $ch2 = curl_init("{$base_url}information/{$company->identification_number}");
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch2, CURLOPT_POSTFIELDS, "");
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$company->api_token}"
            ));
            $response = curl_exec($ch2);
            curl_close($ch2);

            $response_decoded = json_decode($response);
            $filteredDocuments = [];

            if(!$response_decoded->success) {
                throw new Exception("No se obtuvo datos del API");
            }

            // dd($response_decoded->data);
            foreach ($response_decoded->data as $item) {
                if ($item->type_document_id == 15) {
                    $filteredDocuments = $item->documents;
                    break;
                }
            }
            $i = 0;
            foreach($filteredDocuments as $document){
                if($document->cufe != null){
                    $document_pos = DocumentPos::where('prefix', $document->prefix)->where('number', $document->number)->get();
                    if(count($document_pos) == 0){
                        $this->store_sincronize($document);
                        $i++;
                    }
                }
            }
            return [
                "success" => true,
                "message" => $i===0?"Sin documentos por sincronizar":"Se sincronizaron satisfactoriamente, {$i} registros que se habian enviado directamente desde API...",
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function store_sincronize($document)
    {
        $json_api = json_decode($document->request_api);
        $items = json_decode(json_encode($json_api->invoice_lines), true);
        $taxes = Tax::all();
        $tax_totals = [];

        foreach ($items as &$item) {
            $item_search = Item::where('internal_id', $item['code'])->first();
            $total_tax = 0;
            if (isset($item['tax_totals']) && is_array($item['tax_totals'])) {
                foreach ($item['tax_totals'] as $tax_total) {
                    if (isset($tax_total['tax_amount'])) {
                        $total_tax += floatval($tax_total['tax_amount']);
                    }
                    // Acumula el total para cada tax_id
                    $tax_id = $tax_total['tax_id'];
                    if (!isset($tax_totals[$tax_id])) {
                        $tax_totals[$tax_id] = 0;
                    }
                    $tax_totals[$tax_id] += $tax_total['tax_amount'];
                }
            }
            if($item_search) {
                $item['item'] = $item_search->toArray();
                $item['item']['unit_type']['code'] = $item['item']['unit_type_id'];
                $item['item']['edit_sale_unit_price'] = $item['price_amount'];
                $item['item']['tax'] = null;

                $item['item_id'] = $item_search->id;
                $item['quantity'] = $item['invoiced_quantity'];
                $item['total'] = $item['price_amount'];
                $item['total_tax'] = $total_tax;
                $item['unit_type_id'] = $item['item']['unit_type_id'];
                $item['tax_id'] = $item['tax_totals'][0]['tax_id'];
                $item['subtotal'] = $item['price_amount'] - $total_tax;
                $item['discount'] = 0;
                $item['unit_price'] = $item['price_amount'] / $item['invoiced_quantity'];
            }
        }

        foreach ($taxes as &$tax) {
            if (isset($tax_totals[$tax->id])) {
                $tax->total = $tax_totals[$tax->id];
            } else {
                $tax->total = 0; // Si no hay acumulación para ese tax_id, el total es 0
            }
        }
        // dd($items);
        // dd($document->request_api);
        $request = new Request();
        $request->merge([
            'currency_id' => 170,
            'customer_id' => $this->getCustomerId($document),
            'date_expiration' => null,
            'date_issue' => $json_api->date,
            'date_of_issue' => $json_api->date,
            'document_type_id' => '90',
            'electronic' => false,
            'sincronize' => true,
            'establishment_id' => 1,
            'exchange_rate_sale' => 0,
            'paid' => 1,
            'payment_form_id' => $json_api->payment_form->payment_form_id,
            'payment_method_id' => $json_api->payment_form->payment_method_id,
            'prefix' => $document->prefix,
            'series_id' => null,
            'service_invoice' => [],
            'subtotal' => $document->subtotal,
            'time_days_credit' => 0,
            'time_of_issue' => $json_api->time,
            'total_discount' => $document->total_discount,
            'total_tax' => $document->total_tax,
            'total' => $document->total,
            'sale' => $document->total,
            'type_document_id' => $document->type_document_id,
            'type_invoice_id' => null,
            'watch' => false,
            'payments' => [
                [
                    "id" => null,
                    "document_id" => null,
                    "sale_note_id" => null,
                    "date_of_payment" => $json_api->date,
                    "payment_method_type_id" => "01",
                    "payment_destination_id" => "cash",
                    "reference" => null,
                    "payment" => $document->total,
                ]
            ],
            'items' => $items,
            'taxes' => $taxes,
            'xml' => $document->xml,
            'cude' => $document->cufe,
            'request_api' => $document->request_api
        ]);

        try {
            $store = $this->store($request);
            if($store['success'] === false) {
                throw new Exception($store['message']);
            }
        } catch (Exception $e) {
            \Log::error('Sincronizacion POS: '.$e);
        }
    }

    private function getCustomerId($document)
    {
        $customer = Person::where('number', $document->customer_document->identification_number)->first();
        if (!$customer) {
            $customer = new Person();
            $customer->type = 'customers';
            $customer->number = $document->customer_document->identification_number;
            $customer->dv = $document->customer_document->dv ?? NULL;
            $customer->name = $document->customer_document->name;
            $customer->telephone = $document->customer_document->phone;
            $customer->address = $document->customer_document->address;
            $customer->email = $document->customer_document->email;
            $customer->type_regime_id = $document->client->type_regime_id ?? 2;
            $customer->type_person_id = $document->client->type_organization_id ?? 2;
            $customer->type_obligation_id = $document->client->type_liability_id ?? 117;
            $customer->identity_document_type_id = $document->client->type_document_identification_id;
            $customer->code = $document->customer_document->identification_number;
            $customer->country_id = 47;
            $customer->department_id = 779;
            $customer->city_id = $document->customer_document->municipality_id_fact ?? 12688;
            $customer->save();
        }
        return $customer->id;
    }

    public function getCorrelativeInvoice($type_service, $prefix = null, $ignore_state_document_id = false){
        $company = ServiceTenantCompany::firstOrFail();
        $url = $this->getBaseUrlCorrelativeInvoice($type_service, $prefix, $ignore_state_document_id);
        $ch2 = curl_init($url);
//        dd($url, $ch2);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            "Authorization: Bearer {$company->api_token}"
        ));
        $response_data = curl_exec($ch2);
        $err = curl_error($ch2);
        curl_close($ch2);
        $response_encode = json_decode($response_data);
//        \Log::debug($url);
//        \Log::debug($company->api_token);
//        \Log::debug($response_data);
        if($err){
            return null;
        }
        else{
            return $response_encode->number;
        }
    }

    private function getBaseUrlCorrelativeInvoice($type_service, $prefix = null, $ignore_state_document_id = false){
        $base_url = config('tenant.service_fact');
        $url = "{$base_url}ubl2.1/invoice/current_number/{$type_service}";
        if($ignore_state_document_id){
            $val_prefix = $prefix ? $prefix : 'null';
            $url .= "/{$val_prefix}/{$ignore_state_document_id}";
        }else{
            if($prefix){
                $url .= "/{$prefix}";
            }
        }
        return $url;
    }

    protected function validarDigVerifDIAN($nit)
    {
        if(is_numeric(trim($nit))){
            $secuencia = array(3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71);
            $d = str_split(trim($nit));
            krsort($d);
            $cont = 0;
            unset($val);
            foreach ($d as $key => $value) {
                $val[$cont] = $value * $secuencia[$cont];
                $cont++;
            }
            $suma = array_sum($val);
            $div = intval($suma / 11);
            $num = $div * 11;
            $resta = $suma - $num;
            if ($resta == 1)
                return $resta;
            else
                if($resta != 0)
                    return 11 - $resta;
                else
                    return $resta;
        } else {
            return FALSE;
        }
    }

    // Función recursiva para procesar los valores
    public function multiplyMonetaryValues($data, $rate) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->multiplyMonetaryValues($value, $rate);
            }
            else {
                // Detectar claves que representan montos
                if (is_numeric($value) && preg_match('/line_extension_amount|tax_exclusive_amount|tax_inclusive_amount|allowance_total_amount|charge_total_amount|payable_amount|tax_amount|taxable_amount|amount|base_amount|price_amount/i', $key)) {
                    $data[$key] = number_format($value * $rate, 2, '.', '');
                }
            }
        }
        return $data;
    }
}
