<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DocumentPosCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->transform(function($row, $key) {
            $total_paid = number_format($row->payments->sum('payment'), 2, ".", "");
            $total_pending_paid = number_format($row->total - $total_paid, 2, ".", "");
            // Convertir request_api a array de forma segura
            if (is_string($row->request_api)) {
                $request_api = json_decode($row->request_api, true);
            } else {
                $request_api = json_decode(json_encode($row->request_api), true);
            }
            // Asegurar que sea array
            $request_api = is_array($request_api) ? $request_api : [];
            // Obtener type_document_id como entero, si existe
            $type_document_id = isset($request_api['type_document_id']) ? (int) $request_api['type_document_id'] : null;
            // Determinar el tipo de resolución
            if($type_document_id === 1)
                $type_resolution = 'Factura Electronica de Venta';
            else
                if($type_document_id === 15 && $row->electronic)
                    $type_resolution = 'Documento Equivalente POS Electronico';
                else
                    if($type_document_id === 26 && $row->electronic)
                        $type_resolution = 'Nota de crédito al Documento Equivalente';
                    else
                        if($type_document_id === 4 && $row->electronic)
                            $type_resolution = 'Nota crédito';
                        else
                            $type_resolution = 'Documento Ticket Papel';

            return [
                'id' => $row->id,
                'soap_type_id' => $row->soap_type_id,
                'external_id' => $row->external_id,
                'date_of_issue' => $row->date_of_issue ? $row->date_of_issue->format('Y-m-d') : null,
                'identifier' => $row->identifier,
                'full_number' => $row->series.'-'.$row->number,
                'customer_name' => $row->customer->name,
                'customer_number' => $row->customer->number,
                'currency_type_id' => $row->currency->name,
                'total' => number_format($row->total, 2),
                'state_type_id' => $row->state_type_id,
                'state_type_description' => $row->state_type->description,
                'changed' => (bool) $row->changed,
                'enabled_concurrency' => (bool) $row->enabled_concurrency,
                'quantity_period' => $row->quantity_period,
                'type_period' => $row->type_period,
                'apply_concurrency' => (bool) $row->apply_concurrency,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'paid' => (bool) $row->paid,
                'license_plate' => $row->license_plate,
                'total_paid' => $total_paid,
                'total_pending_paid' => $total_pending_paid,
                'user_name' => $row->user ? $row->user->name : '',
                'quotation_number_full' => $row->quotation ? $row->quotation->number_full : '',
                'sale_opportunity_number_full' => isset($row->quotation->sale_opportunity) ? $row->quotation->sale_opportunity->number_full : '',
                'number_full' => $row->number_full,
                'electronic' => $row->electronic,
                'type_resolution' => $type_resolution,
                'plate_number' => $row->plate_number,
                'cash_type' => $row->cash_type,
                'technical_key' => $row->technical_key,
            ];
        });
    }
}
