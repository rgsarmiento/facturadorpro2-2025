<?php

namespace Modules\Payroll\Models;

use App\Models\Tenant\{
    User
};
use App\Models\Tenant\{
    Establishment
};

class BlockPayroll
{
    protected $table = 'co_block_payrolls';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'date_of_issue',
        'time_of_issue',
        'establishment_id',
        'establishment',
        'period',
        'workers_quantity',
        'notes',
        'accrued_total',
        'dedductions_total',
    ];

    protected $casts = [
        'date_of_issue' => 'date',
        'time_of_issue' => 'time',
    ];

    public function getEstablishmentAttribute($value)
    {
        return (is_null($value)) ? null: (object)json_decode($value);
    }

    public function setEstablishmentAttribute($value)
    {
        $this->attributes['establishment'] = (is_null($value)) ? null : json_encode($value);
    }

    public function getPeriodAttribute($value)
    {
        return (is_null($value)) ? null : (object)json_decode($value);
    }

    public function setPeriodAttribute($value)
    {
        $this->attributes['period'] = (is_null($value)) ? null : json_encode($value);
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     *
     * Filtros para listado de nóminas
     *
     * @param $query
     * @param $request
     */
    public function scopeWhereFilterRecords($query, $request)
    {

        if(!is_null($request->value) && $request->value != '')
        {
            if($request->column === 'consecutive')
            {
                return $query->where('consecutive', $request->value);
            }

            return $query->where($request->column, 'like', "%{$request->value}%");
        }

        return $query;
    }

    /**
     * Use in collection
     *
     * @return array
     */
    public function getRowCollection(){
        $filename_xml = null;
        $filename_pdf = null;

        if($this->response_api)
        {
            $response = $this->response_api;
            $response_api_message = isset($response->message) ? $response->message:null;
            $filename_xml = $response->urlpayrollxml ?? null;
            $filename_pdf =  $response->urlpayrollpdf ?? null;
        }
        return [
            'id' => $this->id,
            'date_of_issue' => $this->date_of_issue->format('Y-m-d'),
            'salary' => optional($this->accrued)->salary,
            'accrued_total' => optional($this->accrued)->accrued_total,
            'deductions_total' => optional($this->deduction)->deductions_total,
            'filename_xml' => $filename_xml,
            'filename_pdf' => $filename_pdf,
            'state_document_id' => $this->state_document_id,
            'state_document_name' => optional($this->state_document)->name,
            'btn_query' => $btn_query,
            'response_message_query_zipkey' => $this->response_message_query_zipkey,
            'payroll_type_environment_id' => $this->payroll_type_environment_id,
            'type_payroll_description' => $this->type_payroll_description,
            'btn_adjust_note_elimination' => $btn_adjust_note_elimination,
            'btn_adjust_note_replace' => $btn_adjust_note_replace,
            'affected_adjust_notes' => $affected_adjust_notes,
        ];
    }

    /**
     * Use in resource
     *
     * @return array
     */
    public function getRowResource()
    {
        $filename_xml = null;
        $filename_pdf = null;
        if($this->response_api)
        {
            $response = $this->response_api;
            $response_api_message = isset($response->message) ? $response->message:null;
            $filename_xml = $response->urlpayrollxml ?? null;
            $filename_pdf =  $response->urlpayrollpdf ?? null;
        }
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'date_of_issue' => $this->date_of_issue->format('Y-m-d'),
            'time_of_issue' => $this->time_of_issue,
            'type_document_id' => $this->type_document_id,
            'establishment_id' => $this->establishment_id,
            'establishment' => $this->establishment,
            'head_note' => $this->head_note,
            'foot_note' => $this->foot_note,
            'novelty' => $this->novelty,
            'period' => $this->period,
            'prefix' => $this->prefix,
            'consecutive' => $this->consecutive,
            'number_full' => $this->number_full,
            'payroll_period_id' => $this->payroll_period_id,
            'notes' => $this->notes,
            'worker_id' => $this->worker_id,
            'worker' => $this->worker,
            'worker_full_name' => $this->model_worker->full_name,
            'worker_email' => $this->model_worker->email,
            'payment' => $this->payment,
            'payment_dates' => $this->payment_dates,
            'response_api_message' => $response_api_message,
            'salary' => optional($this->accrued)->salary,
            'accrued_total' => optional($this->accrued)->accrued_total,
            'deductions_total' => optional($this->deduction)->deductions_total,
            'filename_xml' => $filename_xml,
            'filename_pdf' => $filename_pdf,
            'state_document_id' => $this->state_document_id,
            'state_document_name' => optional($this->state_document)->name,
            'response_message_query_zipkey' => $this->response_message_query_zipkey,
            'payroll_type_environment_id' => $this->payroll_type_environment_id,
        ];
    }
}
