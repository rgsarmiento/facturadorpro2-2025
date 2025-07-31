<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Tenant\DocumentPos;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceTenantCompany
};

class DocumentPosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $company = ServiceTenantCompany::select('identification_number')->whereFilterWithOutAllRelations()->firstOrFail();
        $base_url_api = config('tenant.service_fact');
        $response_api = json_decode($this->response_api);
//        \Log::debug(json_encode($response_api));
        $isEqDoc = false;
        if((strpos($this->response_api, 'NCQS') != 0 && strpos($this->response_api, 'La Nota de ajuste cr') != 0 && strpos($this->response_api, 'dito del documento equivalente') != 0) ||
           (strpos($this->response_api, 'NCS') != 0 && strpos($this->response_api, 'La Nota de cr') != 0 && strpos($this->response_api, 'dito electr') != 0))
           $response_api->urlinvoicepdf = str_replace('NCQS', 'NCS', $response_api->urlinvoicepdf);

        $download_xml = $isEqDoc ? "{$base_url_api}download/{$company->identification_number}/{$response_api->urlinvoicexml}" : null;
        $download_pdf = $isEqDoc ? "{$base_url_api}download/{$company->identification_number}/{$response_api->urlinvoicepdf}" : null;
        $customer = is_string($this->customer) ? json_decode($this->customer) : $this->customer;
        if($this->response_api){
            $response = json_decode($this->response_api);
            $response_api_message = isset($response->message) ? $response->message : null;
        }
        $sale_note = DocumentPos::find($this->id);
        $sale_note->payments = self::getTransformPayments($sale_note->payments);
        return [
            'id' => $this->id,
            'correlative_api' => $this->number,
            'number_full' => $this->series."-".$this->number,
            'external_id' => $this->external_id,
            'number' => $this->number_full,
            'identifier' => $this->identifier,
            'date_of_issue' => $this->date_of_issue->format('Y-m-d'),
            'print_ticket' => url('')."/document-pos/print/{$this->external_id}/ticket",
            'print_a4' => url('')."/document-pos/print/{$this->external_id}/a4",
            'print_a5' => url('')."/document-pos/print/{$this->external_id}/a5",
            'print_html' => url('')."/document-pos/print/{$this->external_id}/html",
            'document_pos' => $sale_note,
            'serie' => $this->series,
            'number' => $this->number,
            'customer_email' => $customer->email,
            'customer_phone' => $isEqDoc ? $customer->phone : $customer->telephone,
            'response_api_message' => $isEqDoc ? $response_api_message : null,
            'download_xml' => $download_xml,
            'download_pdf' => $download_pdf,
            'state_document_id' => $this->state_document_id,
            'response_message_query_zipkey' => $isEqDoc ? $this->response_message_query_zipkey : null,
            'type_environment_id' => $this->type_environment_id,
        ];
    }

    public static function getTransformPayments($payments){
        return $payments->transform(function($row, $key){
            return [
                'id' => $row->id,
                'document_pos_id' => $row->document_pos_id,
                'date_of_payment' => $row->date_of_payment->format('Y-m-d'),
                'payment_method_type_id' => $row->payment_method_type_id,
                'has_card' => $row->has_card,
                'card_brand_id' => $row->card_brand_id,
                'reference' => $row->reference,
                'payment' => $row->payment,
                'payment_method_type' => $row->payment_method_type,
                'payment_destination_id' => ($row->global_payment) ? ($row->global_payment->type_record == 'cash' ? 'cash':$row->global_payment->destination_id):null,
                'payment_filename' => ($row->payment_file) ? $row->payment_file->filename:null,
            ];
        });

    }
}
