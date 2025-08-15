<?php

namespace Modules\Report\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DocumentCollection extends ResourceCollection
{


    public function toArray($request) {

        return $this->collection->transform(function($row, $key){

            return [
                'id' => $row->id,
                'date_of_issue' => $row->date_of_issue ? $row->date_of_issue->format('Y-m-d') : null,
                'number' => $row->number ?? '',
                'customer_name' => $row->customer ? $row->customer->name : 'Sin cliente',
                'customer_number' => $row->customer ? $row->customer->number : '',
                'document_type_description' => $row->type_document ? $row->type_document->name : 'Sin tipo',
                'total' => $row->total ?? 0,
                'user_name' => $row->user ? $row->user->name : 'Sin usuario',
                'state_type_description' => 'Estado pendiente', // Simplificado por ahora
                'currency_type_id' => $row->currency ? $row->currency->name : 'PEN',
                'affected_document' => null,
                'quotation_number_full' => '',
                'sale_opportunity_number_full' => '',
            ];
        });
    }
}
