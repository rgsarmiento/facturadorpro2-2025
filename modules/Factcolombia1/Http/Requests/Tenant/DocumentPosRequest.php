<?php

namespace Modules\Factcolombia1\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Factcolombia1\Traits\Tenant\RequestsTrait;

class DocumentPosRequest extends FormRequest
{
    use RequestsTrait;

    /**
     * Form
     * @var string
     */
    public $form = 'document';

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'type_document_id' => 'required|exists:tenant.co_type_documents,id',
            'type_invoice_id' => 'nullable|exists:tenant.co_type_invoices,id',
            // 'customer_id' => 'required|exists:tenant.persons,id',
            // 'client_id' => 'required|exists:tenant.co_clients,id',
            'currency_id' => 'required|exists:tenant.co_currencies,id',
            'date_issue' => 'required|date',
            'calculationrate' => 'nullable|numeric|between:0.00,9999.99',
            'date_expiration' => 'nullable|date',
            'observation' => 'nullable|string',
            'reference_id' => 'nullable|exists:tenant.documents_pos,id',
            'note_concept_id' => 'nullable|exists:tenant.co_note_concepts,id',
            'sale' => 'required|numeric|between:0.00,9999999999.99',
            'total_discount' => 'required|numeric|between:0.00,9999999999.99',
            'taxes' => 'nullable|array',
            'taxes.*.id' => 'nullable|exists:tenant.co_taxes,id',
            'total_tax' => 'required|numeric|between:0.00,9999999999.99',
            'subtotal' => 'required|numeric|between:0.00,9999999999.99',
            'total' => 'required|numeric|between:0.00,9999999999.99',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:tenant.items,id',
            'payment_form_id' => 'nullable|required_if:type_invoice_id,1',
            'payment_method_id' => 'nullable|required_if:type_invoice_id,1',
        ];
    }
}
