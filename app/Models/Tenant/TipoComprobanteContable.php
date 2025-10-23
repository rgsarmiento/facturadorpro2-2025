<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TipoComprobanteContable extends ModelTenant
{

    protected $table = 'tipo_comprobantes_contables';

    protected $fillable = [
        'codigo',
        'nombre',
        'prefijo',
        'consecutivo_actual',
        'estado',
    ];

    protected $casts = [
        'consecutivo_actual' => 'integer',
    ];

    // Relaciones
    public function asientosContables()
    {
        return $this->hasMany(AsientoContable::class, 'tipo_comprobante_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO');
    }

    // Métodos auxiliares
    public function getProximoConsecutivo()
    {
        $this->increment('consecutivo_actual');
        return $this->consecutivo_actual;
    }

    public function generarNumeroComprobante()
    {
        $consecutivo = $this->getProximoConsecutivo();
        $año = date('Y');
        return $this->prefijo . $año . str_pad($consecutivo, 6, '0', STR_PAD_LEFT);
    }
}
