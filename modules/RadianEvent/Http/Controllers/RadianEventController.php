<?php

namespace Modules\RadianEvent\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Factcolombia1\Helpers\HttpConnectionApi;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceCompany
};
use Exception;
use Modules\RadianEvent\Models\{
    ReceivedDocument
};
use Modules\RadianEvent\Http\Resources\{
    ReceivedDocumentCollection
};


class RadianEventController extends Controller
{

    public function reception()
    {
        return view('radianevent::reception.index');
    }


    public function manage()
    {
        return view('radianevent::manage.index');
    }

    public function radianCufe()
    {
        return view('radianevent::reception.radiancufe');
    }




    public function columns()
    {
        return [
            'identification_number' => 'NIT Emisor',
            'name_seller' => 'Nombre emisor',
            'prefix' => 'Prefijo',
            'number' => 'Número de Factura',
        ];
    }


    public function records(Request $request)
    {
        $sortColumn = $request->input('sort_column');
        $sortDirection = $request->input('sort_direction', 'desc');

        $validColumns = ['aceptacion', 'type_document_name', 'date_issue', 'identification_number', 'name_seller', 'prefix', 'number', 'total_tax', 'total'];
        if (!in_array($sortColumn, $validColumns)) {
            $sortColumn = 'id';
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $records = ReceivedDocument::where($request->column, 'like', "%{$request->value}%")
            ->orderBy($sortColumn, $sortDirection);

        return new ReceivedDocumentCollection($records->paginate(config('tenant.items_per_page')));
    }

    public function sendRadianEvent(Request $request)
    {
        $company = ServiceCompany::select('identification_number', 'api_token')->firstOrFail();
        $connection_api = new HttpConnectionApi($company->api_token);

        $params = [
            'event_id' => $request->event_id,
            'document_reference' => [
                'cufe' => $request->cufe
            ]
        ];

        // Incluir type_rejection_id si event_id es igual a 2
        if ($request->event_id == 2) {
            $params['type_rejection_id'] = $request->type_rejection_id;
        }

        $url = "ubl2.1/send-event-data";
        $send_request_to_api = $connection_api->sendRequestToApi($url, $params, 'POST');

        if (isset($send_request_to_api['errors'])) {
            return $this->getGeneralResponse(false, $connection_api->parseErrorsToString($send_request_to_api['errors']));
        }

        if ($send_request_to_api['success']) {
            $received_document = ReceivedDocument::where('cufe', $request->cufe)->first();

            if ($received_document) {
                switch ($request->event_id) {
                    case 1:
                        $received_document->update(['acu_recibo' => 1]);
                        break;
                    case 2:
                        $received_document->update(['rechazo' => 1]);
                        break;
                    case 3:
                        if ($received_document->acu_recibo != 1) {
                            return $this->getGeneralResponse(false, 'Se requiere realizar primero el evento de Acuse de Recibo (event_id = 1).');
                        }
                        $received_document->update(['rec_bienes' => 1]);
                        break;
                    case 4:
                        $received_document->update(['aceptacion' => 1]);
                        break;
                    default:
                        // Manejar otros casos o ignorar
                        break;
                }
            } else {
                if ($request->event_id == 1) {
                    $folder = "radian_reception_documents";
                    $xml = $send_request_to_api['ResponseDian']['Envelope']['Body']['SendEventUpdateStatusResponse']['SendEventUpdateStatusResult']['XmlBase64Bytes'];
                    $file_content = base64_decode($xml);
                    $filename = $send_request_to_api['invoice_number'] . '.xml';
                    $subtotal = $send_request_to_api['invoice_total']-$send_request_to_api['invoice_tax'];
                    $data = [
                            'identification_number' => $send_request_to_api['receiver_id'],
                            'name_seller' => $send_request_to_api['receiver_name'],
                            'state_document_id' => 1,
                            'type_document_id' => 1,
                            'prefix' => 'SETP',
                            'number' => $send_request_to_api['invoice_number'],
                            'xml' => $send_request_to_api['invoice_number'] . '.xml',
                            'cufe' => $send_request_to_api['invoice_cufe'],
                            'date_issue' => $send_request_to_api['invoice_date'],
                            'total' => $send_request_to_api['invoice_total'],
                            'total_tax' => $send_request_to_api['invoice_tax'],
                            'ambient_id' => 2,
                            'dv' => '9',
                            'customer' => $send_request_to_api['transmitter_id'],
                            'sale' => '88888888.00',
                            'subtotal' => $subtotal,
                            'total_discount' => '0.00',
                            'acu_recibo' => 1,
                            'rec_bienes' => 0,
                            'aceptacion' => 0,
                            'rechazo' => 0,
                            'pdf' => $send_request_to_api['invoice_number'] . '.pdf',
                            'response_api' => $send_request_to_api['ResponseDian']
                        ];
                        ReceivedDocument::create($data);
                        Storage::disk('tenant')->put($folder . DIRECTORY_SEPARATOR . $filename, $file_content);
                        return $this->getGeneralResponse(true, 'Evento enviado con éxito y Archivo XML cargado correctamente.');
                } else {
                    return $this->getGeneralResponse(false, 'El documento no existe y no se puede crear para el evento especificado.');
                }
            }

            return $this->getGeneralResponse(true, 'Evento enviado con éxito');
        }

        return $send_request_to_api;
    }





    public function runEvent(Request $request)
    {
        // enviar api para parsear xml y obtener data
        $received_document = ReceivedDocument::findOrFail($request->id);

        $url = "ubl2.1/send-event";
        $company = ServiceCompany::select('identification_number', 'api_token')->firstOrFail();
        $connection_api = new HttpConnectionApi($company->api_token);
        $folder = "radian_reception_documents";
        $filename = $received_document->xml;

        $xml = Storage::disk('tenant')->get($folder.DIRECTORY_SEPARATOR.$filename);

        $params = [
            'event_id' => $request->event_code,
            'base64_attacheddocument_name' => $filename,
            'base64_attacheddocument' => base64_encode($xml),
        ];

        if($request->event_code === '2') $params['type_rejection_id'] = $request->type_rejection_id;

        // dd($params, $received_document, $xml);

        $send_request_to_api = $connection_api->sendRequestToApi($url, $params, 'POST');
        // dd($send_request_to_api);

        //error validacion form request api
        if(isset($send_request_to_api['errors']))  return $this->getGeneralResponse(false, $connection_api->parseErrorsToString($send_request_to_api['errors']));

        if($send_request_to_api['success'])
        {
            return $this->validateResponseApi($send_request_to_api, $connection_api, $received_document, $request->event_code);
        }

        //errores
        return $send_request_to_api;
    }


    private function validateResponseApi($send_request_to_api, HttpConnectionApi $connection_api, $received_document, $event_code)
    {
        //parsear respuesta y verificar
        $send_event_update_status_result = $send_request_to_api['ResponseDian']['Envelope']['Body']['SendEventUpdateStatusResponse']['SendEventUpdateStatusResult'];

        if($send_event_update_status_result['IsValid'] == 'true')
        {
            // actualizar datos
            $this->updateStateByEventCode($received_document, $event_code, $send_request_to_api);

            return $this->getGeneralResponse(true, 'Resultado del Evento: '.$send_event_update_status_result['StatusMessage']);
        }

        // estado rechazado/errores
        $extract_error_response = $send_event_update_status_result['ErrorMessage']['string'] ?? $send_event_update_status_result['StatusDescription'];
        $error_message_response = is_array($extract_error_response) ?  implode(",", $extract_error_response) : $extract_error_response;

        return $this->getGeneralResponse(false, "Resultado del Evento: {$error_message_response}");
    }


    /**
     *
     * Actualizar estados/datos
     *
     * @param  ReceivedDocument $received_document
     * @param  string $event_code
     * @param  array $send_request_to_api
     * @return void
     */
    private function updateStateByEventCode($received_document, $event_code, $send_request_to_api)
    {
        $data_update = [];

        switch ($event_code)
        {
            case '1':
                $data_update = [
                    'acu_recibo' => 1,
                    // 'response_api' => $send_request_to_api, //@todo cada evento genera un response, debe haber un campo para cada uno
                ];
                break;

            case '2':
                $data_update = [
                    'rechazo' => 1,
                    // 'response_api' => $send_request_to_api,
                ];
                break;

            case '3':
                $data_update = [
                    'rec_bienes' => 1,
                    // 'response_api' => $send_request_to_api,
                ];
                break;

            case '4':
                $data_update = [
                    'aceptacion' => 1,
                    // 'response_api' => $send_request_to_api,
                ];
                break;
        }

        $received_document->update($data_update);
    }


    // public function throwException($message)
    // {
    //     throw new Exception($message);
    // }

    public function download($filename)
    {
        return Storage::disk('tenant')->download("radian_reception_documents".DIRECTORY_SEPARATOR.$filename);
    }


    /**
     *
     * Cargar xml
     *
     * @param  Request $request
     * @return array
     */

     public function upload(Request $request)
     {
         if ($request->hasFile('file'))
         {
             try {
                 $folder = "radian_reception_documents";
                 $file = $request->file('file');
                 $file_content = file_get_contents($file);

                 $filename = $file->getClientOriginalName();
                 $extension = $file->getClientOriginalExtension();

                 if ($extension === 'zip') {
                     // Extraer el archivo XML del archivo ZIP
                     $zip = new \ZipArchive();
                     if ($zip->open($file) === true) {
                         $xmlFilename = null;
                         for ($i = 0; $i < $zip->numFiles; $i++) {
                             $filename = $zip->getNameIndex($i);
                             if (pathinfo($filename, PATHINFO_EXTENSION) === 'xml') {
                                 $xmlFilename = $filename;
                                 break;
                             }
                         }
                         if ($xmlFilename === null) {
                             throw new Exception('No se encontró un archivo XML en el archivo ZIP.');
                         }
                         $file_content = $zip->getFromName($xmlFilename);
                         $zip->close();
                         $filename = $xmlFilename;
                         $extension = 'xml';
                     } else {
                         throw new Exception('No se pudo abrir el archivo ZIP.');
                     }
                 }

                 if ($extension === 'pdf') {
                     // Procesar archivo PDF
                     $exist_record = ReceivedDocument::where('xml', str_replace('.pdf', '.xml', $filename))->first();
                     if (!$exist_record) return $this->getGeneralResponse(false, 'Debe cargar el xml previamente.');
                     Storage::disk('tenant')->put($folder . DIRECTORY_SEPARATOR . $filename, $file_content);
                     return $this->getGeneralResponse(true, 'Archivo PDF cargado correctamente.');
                 } elseif ($extension === 'xml') {
                     // Procesar archivo XML
                     if (Storage::disk('tenant')->exists($folder . DIRECTORY_SEPARATOR . $filename)) throw new Exception('El archivo ya fue cargado');
                     $company = ServiceCompany::select('identification_number', 'api_token')->firstOrFail();
                     $connection_api = new HttpConnectionApi($company->api_token);
                     $params = [
                         'xml_document' => base64_encode($file_content),
                         'company_idnumber' => $company->identification_number,
                     ];
                     $url = "process-seller-document-reception";
                     $send_request_to_api = $connection_api->sendRequestToApi($url, $params, 'POST');
                     if (!$send_request_to_api['success']) throw new Exception($send_request_to_api['message']);
                     Storage::disk('tenant')->put($folder . DIRECTORY_SEPARATOR . $filename, $file_content);
                     $data = $send_request_to_api['data'];
                     $data['xml'] = $filename;
                     $data['pdf'] = str_replace('.xml', '.pdf', $filename);
                     ReceivedDocument::create($data);
                     return $this->getGeneralResponse(true, 'Archivo XML cargado correctamente.');
                 } else {
                     throw new Exception('Tipo de archivo no soportado. Por favor, suba un archivo XML, PDF o ZIP.');
                 }
             } catch (Exception $e) {
                 return [
                     'success' => false,
                     'message' => $e->getMessage()
                 ];
             }
         }

         return [
             'success' => false,
             'message' => __('app.actions.upload.error'),
         ];
     }

    /*
    public function upload(Request $request)
    {
        if ($request->hasFile('file'))
        {
            try {

                $folder = "radian_reception_documents";
                $file = $request->file('file');
                $file_content = file_get_contents($file);

                $filename = $file->getClientOriginalName(); //xml
                $filename_pdf = str_replace('.xml', '.pdf', $filename); //pdf

                $parse_filename = explode('.', $filename);
                $extension = end($parse_filename);

                // si es pdf, el registro debe existir

                if($extension === 'pdf')
                {
                    $exist_record = ReceivedDocument::where('xml', str_replace('.pdf', '.xml', $filename))->first();

                    if(!$exist_record) return $this->getGeneralResponse(false, 'Debe cargar el xml previamente.');

                    Storage::disk('tenant')->put($folder.DIRECTORY_SEPARATOR.$filename_pdf, $file_content);

                    return $this->getGeneralResponse(true, 'Archivo cargado correctamente.');
                }


                // proceso inicial para cargar xml

                if(Storage::disk('tenant')->exists($folder.DIRECTORY_SEPARATOR.$filename)) throw new Exception('El archivo ya fue cargado');

                // enviar api para parsear xml y obtener data
                $company = ServiceCompany::select('identification_number', 'api_token')->firstOrFail();
                $connection_api = new HttpConnectionApi($company->api_token);

                $params = [
                    'xml_document' => base64_encode($file_content),
                    'company_idnumber' => $company->identification_number,
                ];

                $url = "process-seller-document-reception";
                $send_request_to_api = $connection_api->sendRequestToApi($url, $params, 'POST');

                if(!$send_request_to_api['success']) throw new Exception($send_request_to_api['message']);
                // enviar api


                //subir archivo
                Storage::disk('tenant')->put($folder.DIRECTORY_SEPARATOR.$filename, $file_content);

                // registrar en bd
                $data = $send_request_to_api['data'];
                $data['xml'] = $filename;
                $data['pdf'] = $filename_pdf;

                ReceivedDocument::create($data);

                return [
                    'success' => true,
                    'message' =>  'Archivo cargado',
                    'send_request_to_api' => $send_request_to_api
                ];

            }
            catch (Exception $e)
            {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }

        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }
    */

}
