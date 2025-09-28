<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsientoAdjunto extends Model
{
    use SoftDeletes;

    protected $table = 'asientos_adjuntos';

    protected $fillable = [
        'asiento_contable_id',
        'nombre_archivo',
        'ruta_archivo',
        'tipo_archivo',
        'tamaño_archivo',
        'fecha_carga',
    ];

    protected $casts = [
        'fecha_carga' => 'datetime',
        'tamaño_archivo' => 'integer',
    ];

    // Relaciones
    public function asientoContable()
    {
        return $this->belongsTo(AsientoContable::class, 'asiento_contable_id');
    }

    // Métodos auxiliares
    public function getTamañoHumano()
    {
        $bytes = $this->tamaño_archivo;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function esImagen()
    {
        return in_array(strtolower($this->tipo_archivo), ['jpg', 'jpeg', 'png', 'gif']);
    }

    public function esPDF()
    {
        return strtolower($this->tipo_archivo) === 'pdf';
    }
}
