<?php

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\{
    User
};
use App\Models\Tenant\{
    Establishment
};
use App\Models\Tenant\ModelTenant;
use Exception;

class BlockPayroll extends ModelTenant
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
        'deductions_total',
        'payload',
        'resolution_id',
        'state_block_id',
    ];

    /**
     * Las columnas que no son mass assignable (incluye las columnas virtuales).
     *
     * @var array
     */
    protected $guarded = [
        'period_start_virtual',
        'period_end_virtual',
    ];

    protected $casts = [
        'date_of_issue' => 'date',
        'time_of_issue' => 'time',
        'payload' => 'array',
        'period_start_virtual' => 'date',
        'period_end_virtual' => 'date',
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
            if ($request->column === 'period') {
                return $query->whereJsonContains('period', $request->value);
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
        // Intentar usar columnas virtuales, si no existen usar JSON
        try {
            $periodStartDate = $this->period_start_virtual ? $this->period_start_virtual->format('Y-m-d') : 
                               ($this->period->period_start ?? '');
            $periodEndDate = $this->period_end_virtual ? $this->period_end_virtual->format('Y-m-d') : 
                             ($this->period->period_end ?? '');
        } catch (Exception $e) {
            // Fallback: usar datos del JSON period
            $periodStartDate = $this->period->period_start ?? '';
            $periodEndDate = $this->period->period_end ?? '';
        }

        return [
            'id' => $this->id,
            'date_of_issue' => $this->date_of_issue->format('Y-m-d'),
            'state_block_id' => $this->state_block_id,
            'state_block_name' => optional($this->state_block)->name,
            'time_of_issue' => $this->time_of_issue,
            'period' => $this->period,
            'period_start_date' => $periodStartDate,
            'period_end_date' => $periodEndDate,
            'workers_quantity' => $this->workers_quantity,
            'accrued_total' => $this->accrued_total,
            'deductions_total' => $this->deductions_total,
        ];
    }

    /**
     * Use in resource
     *
     * @return array
     */
    public function getRowResource()
    {
        // Intentar usar columnas virtuales, si no existen usar JSON
        try {
            $periodStartDate = $this->period_start_virtual ? $this->period_start_virtual->format('Y-m-d') : 
                               ($this->period->period_start ?? '');
            $periodEndDate = $this->period_end_virtual ? $this->period_end_virtual->format('Y-m-d') : 
                             ($this->period->period_end ?? '');
        } catch (Exception $e) {
            // Fallback: usar datos del JSON period
            $periodStartDate = $this->period->period_start ?? '';
            $periodEndDate = $this->period->period_end ?? '';
        }

        return [
            'id' => $this->id,
            'date_of_issue' => $this->date_of_issue->format('Y-m-d'),
            'time_of_issue' => $this->time_of_issue,
            'state_block_id' => $this->state_block_id,
            'state_block_name' => optional($this->state_block)->name,
            'period' => $this->period,
            'period_start_date' => $periodStartDate,
            'period_end_date' => $periodEndDate,
            'workers_quantity' => $this->workers_quantity,
            'accrued_total' => $this->accrued_total,
            'deductions_total' => $this->deductions_total,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resolution()
    {
        return $this->belongsTo(\App\CoreFacturalo\Models\Tenant\TypeDocument::class, 'resolution_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function state_block()
    {
        return $this->belongsTo(\Modules\Factcolombia1\Models\Tenant\StateDocument::class, 'state_block_id');
    }
}
