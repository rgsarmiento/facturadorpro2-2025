<?php

namespace Modules\RadianEvent\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Factcolombia1\Helpers\HttpConnectionApi;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceCompany,
    AdvancedConfiguration,
    TypeDocument
};
use Exception;
use Modules\RadianEvent\Models\{
    ReceivedDocument,
    EmailReadingDetail,
    EmailReading,
};
use Modules\RadianEvent\Http\Resources\{
    ReceivedDocumentCollection
};
use Modules\RadianEvent\Helpers\ZipHelper;
use Illuminate\Support\Facades\DB;
use Modules\Payroll\Traits\UtilityTrait;
use Carbon\Carbon;


class SearchEmailController extends Controller
{

    use UtilityTrait;


    /**
     *
     * Eventos Radian
     * Buscar correos del servidor imap, validarlos, cargar archivos, registrar en documentos recibidos
     *
     * @param  Request $request
     * @return array
     */
    public function searchImapEmails(Request $request)
    {
        // Configurar límites para operaciones largas
        ini_set('max_execution_time', 0); // Sin límite de tiempo
        ini_set('memory_limit', '2048M'); // Aumentar memoria

        \Log::info("Iniciando búsqueda de emails IMAP para fechas: {$request->search_start_date} - {$request->search_end_date}");

        $init_time = Carbon::now();

        $emails = $this->getEmails($request);

        if(!$emails['success']) return $emails;

        $email_reading = EmailReading::create([
            'email_user' => $emails['radian_imap_user'],
            'start_date' => date('Y-m-d'),
            'start_time' => date('H:i:s'),
            'imap_server' => $emails['imap_server'],

            'search_start_date' => $request->search_start_date,
            'search_end_date' => $request->search_end_date,
        ]);

        try
        {
            $data = DB::connection('tenant')->transaction(function() use($emails, $email_reading, $init_time) {

                $all_emails_id = $emails['emails'];
                $mailbox = $emails['mailbox'];
                $total_emails = count($all_emails_id);
                $processed_count = 0;

                \Log::info("Iniciando procesamiento de {$total_emails} emails encontrados");

                $selected_emails = [];

                foreach ($all_emails_id as $key => $email_id)
                {
                    $processed_count++;

                    // Log cada 10 emails procesados para hacer seguimiento
                    if ($processed_count % 10 == 0) {
                        \Log::info("Progreso: {$processed_count}/{$total_emails} emails procesados");
                    }

                    $mail = $mailbox->getMail($email_id);
                    // Debug: procesando email (removido)

                    // validar si el correo cumple las condiciones
                    $data_upload = null;

                    if($this->isValidEmail($mail, $email_reading))
                    {
                        \Log::info("Email VÁLIDO - iniciando procesamiento: {$mail->subject} (ID: {$email_id})");

                        try {
                            // obtener archivos del correo
                            $attachments = collect($mail->getAttachments());
                            $zip_found = false;
                            $zip_content = null;
                            $filename = null;

                            // Debug: buscando archivos ZIP (removido)

                            // Recorrer todos los archivos adjuntos
                            foreach ($attachments as $attachment) {
                                // Verificar si el archivo es un ZIP por su tipo MIME o extensión
                                if (pathinfo($attachment->name, PATHINFO_EXTENSION) == 'zip') {
                                    $zip_found = true;
                                    $zip_content = $attachment->getContents();
                                    $filename = $attachment->name;
                                    // Debug: archivo ZIP encontrado (removido)
                                    break; // Salir del bucle una vez que encontramos un ZIP
                                }
                            }

                            if ($zip_found) {
                                // Debug: procesando archivo ZIP (removido)
                            $extract_zip = (new ZipHelper())->extractZip($zip_content);

                            if(count($extract_zip) === 2) // se valida si tiene 2 archivos, xml y pdf
                            {
                                // Identificar dinámicamente cuál archivo es XML y cuál es PDF
                                $xml_file = null;
                                $pdf_file = null;

                                foreach($extract_zip as $file) {
                                    if(str_contains(strtolower($file['filename']), '.xml')) {
                                        $xml_file = $file;
                                    } elseif(str_contains(strtolower($file['filename']), '.pdf')) {
                                        $pdf_file = $file;
                                    }
                                }

                                if($xml_file && $pdf_file)
                                {
                                    $xml_filename = $xml_file['filename'];
                                    $xml_content = $xml_file['content'];
                                    $pdf_filename = $pdf_file['filename'];
                                    $pdf_content = $pdf_file['content'];

                                    // Debug: archivos identificados (removido)

                                    // verificar si existe el xml
                                    $exist_received_document = ReceivedDocument::select('id')->where('xml', $xml_filename)->first();

                                    if(!$exist_received_document)
                                    {
                                        // enviar api para parsear xml y obtener data
                                        $send_request_to_api = $this->sendXmlToApi($xml_content);

                                        // registrar en bd
                                        $email_reading_detail = $this->saveEmailReadingDetail($email_reading, $mail);

                                        if($send_request_to_api['success'])
                                        {
                                            $data = $send_request_to_api['data'];
                                            $data['xml'] = $xml_filename;
                                            $data['pdf'] = $pdf_filename;

                                            $this->updateEmailReadingDetail($email_reading_detail, [
                                                'success' => true,
                                                'response_api' => $data
                                            ]);

                                            //insertar detalle
                                            $email_reading_detail->received_document()->create($data);

                                            //subir archivo
                                            $this->uploadFile($pdf_filename, $pdf_content);
                                            $this->uploadFile($xml_filename, $xml_content);

                                        }
                                        else
                                        {
                                            $this->updateEmailReadingDetail($email_reading_detail, [
                                                'success' => false,
                                                'response_api' => $send_request_to_api
                                            ]);

                                        }
                                    } else {
                                        \Log::warning("XML ya existe en la base de datos: {$xml_filename} para email ID: {$email_id}");
                                    }
                                } else {
                                    \Log::error("Archivos ZIP no contiene XML y PDF válidos para email ID: {$email_id}. Archivos encontrados: " .
                                        implode(', ', array_map(function($f) { return $f['filename']; }, $extract_zip)));
                                }
                            } else {
                                \Log::error("ZIP no contiene exactamente 2 archivos para email ID: {$email_id}. Archivos encontrados: " . count($extract_zip));
                            }
                        } else {
                            \Log::warning("No se encontró archivo ZIP en email ID: {$email_id}");
                        }
                    } catch (\Exception $e) {
                            \Log::error("Error procesando email ID {$email_id}: " . $e->getMessage());
                            \Log::error("Stack trace: " . $e->getTraceAsString());
                        }
                    } else {
                        // Debug: email no válido (removido)
                    }
                }

                $email_reading->update([
                    'end_date'=> date('Y-m-d'),
                    'end_time'=> date('H:i:s'),
                    'success'=> true,
                ]);

                // return $this->getGeneralResponse(true, "Proceso realizado correctamente: {$email_reading->details()->count()} correos fueron registrados.");

                $end_time = Carbon::now();
                $diff_in_seconds = $end_time->diffInSeconds($init_time);
                $processed_emails = $email_reading->details()->count();

                \Log::info("Procesamiento completado: {$processed_emails} correos registrados en {$diff_in_seconds} segundos");

                return [
                    'success' => true,
                    'message' => "Proceso realizado correctamente: {$processed_emails} correos fueron registrados de {$total_emails} emails procesados en " . gmdate("H:i:s", $diff_in_seconds),
                    'data' => [
                        'diff_in_seconds' => $diff_in_seconds,
                        'total_emails_found' => $total_emails,
                        'emails_processed' => $processed_emails,
                        'execution_time' => gmdate("H:i:s", $diff_in_seconds),
                    ],
                ];

            });

            return $data;
        }
        catch (Exception $e)
        {
            $email_reading->errors = $e->getMessage();
            return $this->getErrorFromException($e->getMessage(), $e);
        }

    }


    /**
     * Obtener el estado del último procesamiento de emails
     *
     * @return array
     */
    public function getLastProcessingStatus()
    {
        try {
            $lastEmailReading = EmailReading::with(['details.received_document'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastEmailReading) {
                return $this->getGeneralResponse(false, 'No se encontraron registros de procesamiento de emails');
            }

            $totalDetails = $lastEmailReading->details()->count();

            // Contar emails exitosos basándose en si tienen documento recibido asociado
            $successfulDetails = $lastEmailReading->details()
                ->whereHas('received_document')
                ->count();

            return [
                'success' => true,
                'data' => [
                    'last_processing' => $lastEmailReading,
                    'total_emails_processed' => $totalDetails,
                    'successful_emails' => $successfulDetails,
                    'failed_emails' => $totalDetails - $successfulDetails,
                    'processing_date' => $lastEmailReading->start_date,
                    'processing_time' => $lastEmailReading->start_time,
                    'is_completed' => $lastEmailReading->success,
                    'search_range' => [
                        'start' => $lastEmailReading->search_start_date,
                        'end' => $lastEmailReading->search_end_date
                    ]
                ]
            ];
        } catch (Exception $e) {
            return $this->getErrorFromException('Error obteniendo estado del procesamiento', $e);
        }
    }


    /**
     *
     * Conectarse con servidor imap y obtener correos, aplicar criterios de busqueda
     *
     * @param  Request $request
     * @return array
     */
    private function getEmails(Request $request)
    {

        $advanced_configuration = AdvancedConfiguration::selectImapColumns()->firstOrFail();

        $imap_server = '{'.$advanced_configuration->radian_imap_host.':'.$advanced_configuration->radian_imap_port.'/imap/'.$advanced_configuration->radian_imap_encryption.'}INBOX';

        $mailbox = new \PhpImap\Mailbox($imap_server, $advanced_configuration->radian_imap_user, $advanced_configuration->radian_imap_password);

        try
        {
            $criteria = $this->getSearchCriteria($request);

            $all_emails_id = $mailbox->searchMailbox($criteria);

            if(!$all_emails_id) return $this->getGeneralResponse(false, 'No se encontraron correos.');

            return [
                'success' => true,
                'imap_server' => $imap_server,
                'radian_imap_user' => $advanced_configuration->radian_imap_user,
                'emails' => $all_emails_id,
                'mailbox' => $mailbox,
                'criteria' => $criteria,
                'quantity_all_emails_id' => count($all_emails_id),
            ];

        }
        catch(PhpImap\Exceptions\ConnectionException $ex)
        {
            return $this->getGeneralResponse(false, 'Conexión IMAP fallida: ' . implode(",", $ex->getErrors('all')));
        }

    }


    /**
     *
     * Aplicar criterios de busqueda por rangos de fecha
     *
     * @param  Request $request
     * @return string
     */
    private function getSearchCriteria(Request $request)
    {
        $search_start_date = $request->search_start_date;
        $search_end_date = $request->search_end_date;

        if(!$search_start_date || !$search_end_date) return $this->getGeneralResponse(false, 'Los campos fecha inicio y término son obligatorios');

        //si se busca por dia
        if($search_start_date === $search_end_date)
        {
            $criteria = 'ON "'.$search_start_date.'"';
        }
        // si se busca por rango
        else
        {
            $parse_search_start_date = Carbon::parse($search_start_date)->format('j F Y');
            $parse_search_end_date = Carbon::parse($search_end_date)->addDay(1)->format('j F Y');

            $criteria = 'SINCE "'.$parse_search_start_date.'" BEFORE "'.$parse_search_end_date.'"';
        }

        return $criteria;
    }


    /**
     *
     * Actualizar detalle con respuesta de validaciones de la Api
     *
     * @param  EmailReadingDetail $email_reading_detail
     * @param  array $data
     * @return void
     */
    private function updateEmailReadingDetail(&$email_reading_detail, $data)
    {
        $email_reading_detail->api_validation_response = $data;
        $email_reading_detail->save();
    }


    /**
     *
     * Registrar detalle del email
     *
     * @param  EmailReading $email_reading
     * @param   $mail
     * @return void
     */
    private function saveEmailReadingDetail($email_reading, $mail)
    {

        return $email_reading->details()->create([
            'email_user' => $email_reading->email_user,
            'email_id' => $mail->id,
            'subject' => $mail->subject,
            'from_host' => $mail->fromHost,
            'from_name' => $mail->fromName ? $mail->fromName : $mail->fromAddress,
            'from_address' => $mail->fromAddress,
            'sender_host' => $mail->senderHost,
        ]);
    }


    /**
     *
     * @param  string $filename
     * @param  string $content
     * @param  string $folder
     * @return void
     */
    private function uploadFile($filename, $content, $folder = 'radian_reception_documents')
    {
        Storage::disk('tenant')->put($folder.DIRECTORY_SEPARATOR.$filename, $content);
    }


    /**
     *
     * Enviar xml a la api para validar datos
     * Obtener datos parseados para el registro en documentos recibidos
     *
     * @param  string $xml_content
     * @return array
     */
    private function sendXmlToApi($xml_content)
    {
        $company = ServiceCompany::select('identification_number', 'api_token')->firstOrFail();
        $connection_api = new HttpConnectionApi($company->api_token);

        $params = [
            'xml_document' => base64_encode($xml_content),
            'company_idnumber' => $company->identification_number,
        ];

        return $connection_api->sendRequestToApi('process-seller-document-reception', $params, 'POST');
    }


    /**
     *
     * Validar si email cumple con las condiciones para procesarlo
     *
     * @param  $mail
     * @param  EmailReading $email_reading
     * @return bool
     */
    public function isValidEmail($mail, $email_reading)
    {
        $subject = $mail->subject;
    // Debug: evaluando email (removido)

        // permitir correos reenviados
        if (strpos($subject, 'Fwd: ') === 0) {
            $subject = substr($subject, 5);
        } elseif (strpos($subject, 'RV: ') === 0) {
            $subject = substr($subject, 4);
        }

        $parse_subject = explode(';',  $subject);
        $quantity_items = count($parse_subject);

        $email_reading_detail = EmailReadingDetail::where('co_email_reading_id', $email_reading->id)
                                                    ->where('email_id', $mail->id)
                                                    ->select('id')
                                                    ->first();

        // validar si es que no existe el email registrado en esta sesión específica
        if($quantity_items > 0 && !$email_reading_detail)
        {
            // Debug: email no procesado previamente (removido)

            if(isset($parse_subject[3]))
            {
                $type_document_code = trim($parse_subject[3]);
                $exist_type_document = TypeDocument::where('code', $type_document_code)->select('id')->first();

                if($quantity_items >= 4 && is_numeric($parse_subject[0]) && $exist_type_document && $mail->hasAttachments())
                {
                    $attachments = $mail->getAttachments();
                    if(count($attachments) > 0)
                    {
                        // Verificar si tiene un archivo ZIP
                        foreach ($attachments as $attachment) {
                            if (pathinfo($attachment->name, PATHINFO_EXTENSION) == 'zip') {
                                \Log::info("Email válido encontrado: {$mail->subject} (ID: {$mail->id})");
                                return true;
                            }
                        }
                        // Debug: email ignorado sin ZIP (removido)
                    }
                }
                else {
                    // Debug: email no cumple condiciones (removido)
                }
            }
        }
        else {
            if($email_reading_detail) {
                // Debug: email ya procesado (removido)
            } else {
                // Debug: email sin elementos en subject (removido)
            }
        }

        return false;
    }


}
