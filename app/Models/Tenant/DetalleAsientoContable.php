<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleAsientoContable extends ModelTenant
{
    use SoftDeletes;

    protected $table = 'detalle_asientos_contables';

    protected $fillable = [
        'asiento_contable_id',
        'cuenta_contable_id',
        'person_id',
        'debito',
        'credito',
        'concepto',
        'orden',
    ];

    protected $casts = [
        'debito' => 'decimal:2',
        'credito' => 'decimal:2',
        'orden' => 'integer',
    ];

    // Relaciones
    public function asientoContable()
    {
        return $this->belongsTo(AsientoContable::class, 'asiento_contable_id');
    }

    public function cuentaContable()
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_contable_id');
    }

    public function tercero()
    {
        return $this->belongsTo(\App\Models\Tenant\Person::class, 'person_id');
    }

    // Métodos auxiliares
    public function esDebito()
    {
        return $this->debito > 0;
    }

    public function esCredito()
    {
        return $this->credito > 0;
    }

    public function getValor()
    {
        return $this->esDebito() ? $this->debito : $this->credito;
    }

    public function getTipo()
    {
        return $this->esDebito() ? 'DEBITO' : 'CREDITO';
    }
}
