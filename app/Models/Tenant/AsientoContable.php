<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant\User;

class AsientoContable extends ModelTenant
{
    use SoftDeletes;

    protected $table = 'asientos_contables';

    protected $fillable = [
        'tipo_comprobante_id',
        'numero_comprobante',
        'consecutivo',
        'fecha_asiento',
        'period_id',
        'concepto',
        'total_debito',
        'total_credito',
        'tipo_origen',
        'modulo_origen',
        'documento_origen_id',
        'estado',
        'usuario_creacion',
        'fecha_creacion',
        'usuario_confirmacion',
        'fecha_confirmacion',
        'usuario_anulacion',
        'fecha_anulacion',
        'motivo_anulacion',
    ];

    protected $casts = [
        'fecha_asiento' => 'date',
        'fecha_creacion' => 'datetime',
        'fecha_confirmacion' => 'datetime',
        'fecha_anulacion' => 'datetime',
        'total_debito' => 'decimal:2',
        'total_credito' => 'decimal:2',
    ];

    // Relaciones
    public function tipoComprobante()
    {
        return $this->belongsTo(TipoComprobanteContable::class, 'tipo_comprobante_id');
    }

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleAsientoContable::class, 'asiento_contable_id')->orderBy('orden');
    }

    public function adjuntos()
    {
        return $this->hasMany(AsientoAdjunto::class, 'asiento_contable_id');
    }

    public function usuarioCreacion()
    {
        return $this->belongsTo(User::class, 'usuario_creacion');
    }

    public function usuarioConfirmacion()
    {
        return $this->belongsTo(User::class, 'usuario_confirmacion');
    }

    public function usuarioAnulacion()
    {
        return $this->belongsTo(User::class, 'usuario_anulacion');
    }

    // Scopes
    public function scopeBorradores($query)
    {
        return $query->where('estado', 'BORRADOR');
    }

    public function scopeConfirmados($query)
    {
        return $query->where('estado', 'CONFIRMADO');
    }

    public function scopeAnulados($query)
    {
        return $query->where('estado', 'ANULADO');
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_asiento', [$fechaInicio, $fechaFin]);
    }

    // Métodos auxiliares
    public function esBorrador()
    {
        return $this->estado === 'BORRADOR';
    }

    public function esConfirmado()
    {
        return $this->estado === 'CONFIRMADO';
    }

    public function esAnulado()
    {
        return $this->estado === 'ANULADO';
    }

    public function puedeEditarse()
    {
        return $this->esBorrador();
    }

    public function puedeAnularse()
    {
        return $this->esConfirmado();
    }

    public function estaBalanceado()
    {
        return bccomp($this->total_debito, $this->total_credito, 2) === 0;
    }

    public function calcularTotales()
    {
        $this->total_debito = $this->detalles()->sum('debito');
        $this->total_credito = $this->detalles()->sum('credito');
        $this->save();
    }

    public function confirmar($usuarioId)
    {
        if (!$this->esBorrador()) {
            throw new \Exception('Solo se pueden confirmar asientos en estado borrador');
        }

        if (!$this->estaBalanceado()) {
            throw new \Exception('El asiento no está balanceado. No se puede confirmar.');
        }

        $this->estado = 'CONFIRMADO';
        $this->usuario_confirmacion = $usuarioId;
        $this->fecha_confirmacion = now();
        $this->save();

        // Si es comprobante de Saldos Iniciales (código 24), actualizar saldo_inicial en cuentas_contables
        $tipoComprobante = $this->tipoComprobante;
        if ($tipoComprobante && $tipoComprobante->codigo === '24') {
            // Actualizar el saldo inicial de cada cuenta según los detalles del asiento
            foreach ($this->detalles as $detalle) {
                $cuenta = $detalle->cuentaContable;
                if ($cuenta) {
                    // Calcular el saldo inicial según la naturaleza
                    $nuevoSaldoInicial = 0;
                    if ($cuenta->naturaleza === 'debito') {
                        $nuevoSaldoInicial = floatval($detalle->debito) - floatval($detalle->credito);
                    } else {
                        $nuevoSaldoInicial = floatval($detalle->credito) - floatval($detalle->debito);
                    }

                    // Actualizar el saldo inicial de la cuenta
                    $cuenta->update([
                        'saldo_inicial' => $nuevoSaldoInicial
                    ]);
                }
            }
        }

        // Actualizar saldos de cuentas
        $this->actualizarSaldosCuentas();
    }

    public function anular($usuarioId, $motivo)
    {
        if (!$this->puedeAnularse()) {
            throw new \Exception('Solo se pueden anular asientos confirmados');
        }

        $this->estado = 'ANULADO';
        $this->usuario_anulacion = $usuarioId;
        $this->fecha_anulacion = now();
        $this->motivo_anulacion = $motivo;
        $this->save();

        // Reversar saldos de cuentas
        $this->reversarSaldosCuentas();
    }

    private function actualizarSaldosCuentas()
    {
        foreach ($this->detalles as $detalle) {
            $cuenta = $detalle->cuentaContable;

            if ($cuenta->naturaleza === 'DEBITO') {
                $cuenta->saldo_anterior += ($detalle->debito - $detalle->credito);
            } else {
                $cuenta->saldo_anterior += ($detalle->credito - $detalle->debito);
            }

            $cuenta->save();
        }
    }

    private function reversarSaldosCuentas()
    {
        foreach ($this->detalles as $detalle) {
            $cuenta = $detalle->cuentaContable;

            if ($cuenta->naturaleza === 'DEBITO') {
                $cuenta->saldo_anterior -= ($detalle->debito - $detalle->credito);
            } else {
                $cuenta->saldo_anterior -= ($detalle->credito - $detalle->debito);
            }

            $cuenta->save();
        }
    }
}
