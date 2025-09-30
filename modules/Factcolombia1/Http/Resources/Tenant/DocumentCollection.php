<?php

namespace Modules\Factcolombia1\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Factcolombia1\Models\TenantService\{
    Company as ServiceTenantCompany
};

class DocumentCollection extends ResourceCollection
{


    public function toArray($request) {

        $company = ServiceTenantCompany::select('identification_number')->whereFilterWithOutAllRelations()->firstOrFail();

        return $this->collection->transform(function($row, $key) use ($company){

            $base_url_api = config('tenant.service_fact');

            // Determinar el prefijo correcto según el tipo de documento
            $document_prefix = $this->getDocumentPrefix($row->type_document_id, $row->type_document->name);

            // Si response_api no está disponible (optimización), usar enlaces por defecto
            if(isset($row->response_api) && $row->response_api_invoice && $row->response_api_invoice->urlinvoicexml) {
                $download_xml = "{$base_url_api}download/{$company->identification_number}/{$row->response_api_invoice->urlinvoicexml}";
            } else {
                $download_xml = "{$base_url_api}download/{$company->identification_number}/{$document_prefix}-{$row->prefix}{$row->number}.xml";
            }

            if(isset($row->response_api) && $row->response_api_invoice && $row->response_api_invoice->urlinvoicepdf) {
                $download_pdf = "{$base_url_api}download/{$company->identification_number}/{$row->response_api_invoice->urlinvoicepdf}";
            } else {
                $download_pdf = "{$base_url_api}download/{$company->identification_number}/{$document_prefix}-{$row->prefix}{$row->number}.pdf";
            }

            //mostrar el boton consultar si el estado es registrado y el entorno es habilitacion
            // shipping_two_steps aplica para documentos generados en habilitacion

            $btn_query = false;

            if($row->state_document_id === 1 && $row->type_environment_id == 2 && $row->shipping_two_steps){
                $btn_query = true;
            }

            return [
                'id' => $row->id,
                'date_of_issue' => $row->date_of_issue->format('Y-m-d'),
                'download_xml' => $download_xml,
                'download_pdf' => $download_pdf,
                'response_api_invoice' => isset($row->response_api) ? $row->response_api_invoice : (object)['message' => '', 'urlinvoicexml' => null, 'urlinvoicepdf' => null],
                'acknowledgment_received' => ($row->acknowledgment_received != null) ? ($row->acknowledgment_received == 1 ? 'Aceptado' : 'Rechazado'):'',
                'customer_name' => $row->customer->name,
                'customer_number' => $row->customer->number,
                'customer_identity_document_type' => $row->customer->identity_document_type->name,
                'number_full' => "{$row->prefix}-{$row->number}",
                'type_document_name' => $row->type_document->name,
                'state_document_name' => $row->state_document->name,
                'currency_name' => $row->currency->name,
                'sale' => $row->sale,
                'total_discount' => $row->total_discount,
                'total_tax' => $row->total_tax,
                'subtotal' => $row->subtotal,
                'total' => (in_array($row->type_document_id,[3])) ? '-'.$row->total : $row->total,
                'type_environment_id' => $row->type_environment_id,
                'state_document_id' => $row->state_document_id,
                'btn_query' => $btn_query,

            ];

        });
    }

    /**
     * Obtiene el prefijo correcto para el tipo de documento
     */
    private function getDocumentPrefix($type_document_id, $type_document_name)
    {
        \Log::info("DocumentCollection DEBUG - type_document_id: {$type_document_id}, type_document_name: {$type_document_name}");

        // Usar el nombre del tipo de documento para determinar el prefijo correcto
        $document_name_lower = strtolower($type_document_name);

        if (strpos($document_name_lower, 'nota') !== false && strpos($document_name_lower, 'crédito') !== false) {
            \Log::info("DocumentCollection - Detectado 'nota crédito' en nombre, asignando NCS");
            return 'NCS';
        }

        if (strpos($document_name_lower, 'nota') !== false && strpos($document_name_lower, 'débito') !== false) {
            \Log::info("DocumentCollection - Detectado 'nota débito' en nombre, asignando NDS");
            return 'NDS';
        }

        // Si contiene "factura" o no es nota, usar FES
        \Log::info("DocumentCollection - Asignando FES para documento: {$type_document_name}");
        return 'FES';
    }
}
