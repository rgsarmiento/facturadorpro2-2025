<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CuentaContableCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function toArray($request)
    {
        return $this->collection->transform(function($row, $key) {
            return [
                'id' => $row->id,
                'codigo' => $row->codigo,
                'nombre' => $row->nombre,
                'tipo_cuenta' => $row->tipo_cuenta,
                'naturaleza' => $row->naturaleza,
                'nivel' => $row->nivel,
                'cuenta_padre_id' => $row->cuenta_padre_id,
                'cuenta_padre' => $row->cuentaPadre ? [
                    'id' => $row->cuentaPadre->id,
                    'codigo' => $row->cuentaPadre->codigo,
                    'nombre' => $row->cuentaPadre->nombre
                ] : null,
                'cuenta_padre_codigo' => $row->cuentaPadre ? $row->cuentaPadre->codigo : null,
                'cuenta_padre_nombre' => $row->cuentaPadre ? $row->cuentaPadre->nombre : null,
                'descripcion' => $row->descripcion,
                'activa' => $row->activa,
                'activa_label' => $row->activa ? 'Activa' : 'Inactiva',
                'permite_movimiento' => $row->permite_movimiento,
                'permite_movimiento_label' => $row->permite_movimiento ? 'Sí' : 'No',
                'saldo_inicial' => $row->saldo_inicial,
                'saldo_actual' => $row->saldo_actual,
                'codigo_niif' => $row->codigo_niif,
                'created_at' => $row->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $row->updated_at->format('Y-m-d H:i:s'),
            ];
        });
    }
}
