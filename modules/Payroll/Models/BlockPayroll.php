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
        'dedductions_total',
        'payload',
    ];

    protected $casts = [
        'date_of_issue' => 'date',
        'time_of_issue' => 'time',
        'payload' => 'array',
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
        return [
            'id' => $this->id,
            'date_of_issue' => $this->date_of_issue->format('Y-m-d'),
            'time_of_issue' => $this->time_of_issue->format('H:i:s'),
            'period' => $this->period,
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
        return [
            'id' => $this->id,
            'date_of_issue' => $this->date_of_issue->format('Y-m-d'),
            'time_of_issue' => $this->time_of_issue->format('H:i:s'),
            'period' => $this->period,
            'workers_quantity' => $this->workers_quantity,
            'accrued_total' => $this->accrued_total,
            'deductions_total' => $this->deductions_total,
        ];
    }
}
