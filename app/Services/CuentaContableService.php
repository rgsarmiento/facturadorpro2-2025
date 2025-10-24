<?php

namespace App\Services;

use App\Models\Tenant\CuentaContable;
use App\Models\Tenant\DetalleAsientoContable;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Servicio para gestionar operaciones contables sobre cuentas
 * Implementa la lógica de actualización de saldos según partida doble
 */
class CuentaContableService
{
    /**
     * Actualiza los saldos de las cuentas afectadas por un asiento contable
     *
     * @param int $asientoId ID del asiento contable
     * @param string $operacion 'crear' | 'actualizar' | 'eliminar' | 'anular'
     * @return void
     * @throws Exception
     */
    public function actualizarSaldosPorAsiento($asientoId, $operacion = 'crear')
    {
        // Obtener todos los detalles del asiento
        $detalles = DetalleAsientoContable::on('tenant')
            ->where('asiento_contable_id', $asientoId)
            ->with('cuentaContable')
            ->get();

        if ($detalles->isEmpty()) {
            return;
        }

        // Agrupar movimientos por cuenta
        $movimientosPorCuenta = [];

        foreach ($detalles as $detalle) {
            $cuentaId = $detalle->cuenta_contable_id;

            if (!isset($movimientosPorCuenta[$cuentaId])) {
                $movimientosPorCuenta[$cuentaId] = [
                    'debitos' => 0,
                    'creditos' => 0
                ];
            }

            $movimientosPorCuenta[$cuentaId]['debitos'] += $detalle->debito;
            $movimientosPorCuenta[$cuentaId]['creditos'] += $detalle->credito;
        }

        // Actualizar cada cuenta afectada
        foreach ($movimientosPorCuenta as $cuentaId => $movimientos) {
            $this->actualizarSaldoCuenta(
                $cuentaId,
                $movimientos['debitos'],
                $movimientos['creditos'],
                $operacion
            );
        }
    }

    /**
     * Actualiza el saldo de una cuenta específica según su naturaleza
     *
     * @param int $cuentaId
     * @param float $debito
     * @param float $credito
     * @param string $operacion 'crear' | 'actualizar' | 'eliminar' | 'anular'
     * @return void
     * @throws Exception
     */
    private function actualizarSaldoCuenta($cuentaId, $debito, $credito, $operacion)
    {
        $cuenta = CuentaContable::on('tenant')->lockForUpdate()->find($cuentaId);

        if (!$cuenta) {
            throw new Exception("Cuenta contable {$cuentaId} no encontrada");
        }

        // No permitir movimientos en cuentas que no lo permiten
        if (!$cuenta->permite_movimiento) {
            throw new Exception("La cuenta {$cuenta->codigo} - {$cuenta->nombre} no permite movimientos directos");
        }

        // Calcular el efecto neto del movimiento según la naturaleza de la cuenta
        $efectoNeto = $this->calcularEfectoNeto($cuenta->naturaleza, $debito, $credito, $operacion);

        // Actualizar el saldo actual
        $cuenta->saldo_actual = $cuenta->saldo_actual + $efectoNeto;
        $cuenta->save();

        // Actualizar recursivamente las cuentas padre
        if ($cuenta->cuenta_padre_id) {
            $this->actualizarSaldosCuentasPadre($cuenta->cuenta_padre_id);
        }
    }

    /**
     * Calcula el efecto neto de un movimiento según la naturaleza de la cuenta
     *
     * Naturaleza DÉBITO:
     *   - Aumenta con débitos (+)
     *   - Disminuye con créditos (-)
     *
     * Naturaleza CRÉDITO:
     *   - Aumenta con créditos (+)
     *   - Disminuye con débitos (-)
     *
     * @param string $naturaleza 'debito' | 'credito'
     * @param float $debito
     * @param float $credito
     * @param string $operacion 'crear' | 'actualizar' | 'eliminar' | 'anular'
     * @return float
     */
    private function calcularEfectoNeto($naturaleza, $debito, $credito, $operacion)
    {
        // Si es eliminación o anulación, invertir el efecto
        $multiplicador = ($operacion === 'eliminar' || $operacion === 'anular') ? -1 : 1;

        if ($naturaleza === 'debito') {
            // Cuenta de naturaleza débito: +débito -crédito
            return ($debito - $credito) * $multiplicador;
        } else {
            // Cuenta de naturaleza crédito: +crédito -débito
            return ($credito - $debito) * $multiplicador;
        }
    }

    /**
     * Actualiza recursivamente los saldos de las cuentas padre
     * consolidando los saldos de todas sus cuentas hijas
     *
     * @param int $cuentaPadreId
     * @return void
     */
    private function actualizarSaldosCuentasPadre($cuentaPadreId)
    {
        if (!$cuentaPadreId) {
            return;
        }

        $cuentaPadre = CuentaContable::on('tenant')->lockForUpdate()->find($cuentaPadreId);

        if (!$cuentaPadre) {
            return;
        }

        // Obtener todas las cuentas hijas
        $cuentasHijas = CuentaContable::on('tenant')
            ->where('cuenta_padre_id', $cuentaPadreId)
            ->get();

        // Consolidar saldos de las cuentas hijas
        $saldoInicialConsolidado = 0;
        $saldoActualConsolidado = 0;
        $saldoAnteriorConsolidado = 0;

        foreach ($cuentasHijas as $hija) {
            $saldoInicialConsolidado += $hija->saldo_inicial ?? 0;
            $saldoActualConsolidado += $hija->saldo_actual ?? 0;
            $saldoAnteriorConsolidado += $hija->saldo_anterior ?? 0;
        }

        // Actualizar la cuenta padre
        $cuentaPadre->saldo_inicial = $saldoInicialConsolidado;
        $cuentaPadre->saldo_actual = $saldoActualConsolidado;
        $cuentaPadre->saldo_anterior = $saldoAnteriorConsolidado;
        $cuentaPadre->save();

        // Si la cuenta padre tiene padre, continuar recursivamente
        if ($cuentaPadre->cuenta_padre_id) {
            $this->actualizarSaldosCuentasPadre($cuentaPadre->cuenta_padre_id);
        }
    }

    /**
     * Revierte los movimientos de un asiento (para eliminación o anulación)
     *
     * @param int $asientoId
     * @return void
     * @throws Exception
     */
    public function revertirSaldosPorAsiento($asientoId)
    {
        $this->actualizarSaldosPorAsiento($asientoId, 'eliminar');
    }

    /**
     * Actualiza saldos cuando se modifica un asiento existente
     * Primero revierte los movimientos anteriores y luego aplica los nuevos
     *
     * @param int $asientoId
     * @param array $detallesAnteriores Detalles del asiento antes de la modificación
     * @return void
     * @throws Exception
     */
    public function actualizarSaldosPorModificacion($asientoId, $detallesAnteriores)
    {
        // 1. Revertir movimientos anteriores
        foreach ($detallesAnteriores as $detalleAnterior) {
            $this->actualizarSaldoCuenta(
                $detalleAnterior['cuenta_contable_id'],
                $detalleAnterior['debito'],
                $detalleAnterior['credito'],
                'eliminar'
            );
        }

        // 2. Aplicar nuevos movimientos
        $this->actualizarSaldosPorAsiento($asientoId, 'crear');
    }

    /**
     * Recalcula todos los saldos de las cuentas padre de forma recursiva
     * Útil para sincronización o corrección de inconsistencias
     *
     * @return void
     */
    public function recalcularTodosSaldosPadre()
    {
        // Obtener todas las cuentas padre (que tienen hijas)
        $cuentasPadre = CuentaContable::on('tenant')
            ->whereHas('cuentasHijas')
            ->orderBy('nivel', 'desc') // Empezar desde el nivel más profundo
            ->get();

        foreach ($cuentasPadre as $padre) {
            $this->actualizarSaldosCuentasPadre($padre->id);
        }
    }

    /**
     * Valida que un asiento cumpla con la partida doble
     *
     * @param array $detalles Array de detalles del asiento
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarPartidaDoble($detalles)
    {
        $totalDebito = 0;
        $totalCredito = 0;

        foreach ($detalles as $detalle) {
            $totalDebito += floatval($detalle['debito'] ?? 0);
            $totalCredito += floatval($detalle['credito'] ?? 0);
        }

        $diferencia = abs($totalDebito - $totalCredito);

        // Permitir una diferencia mínima por redondeos (0.01)
        if ($diferencia > 0.01) {
            return [
                'valido' => false,
                'mensaje' => sprintf(
                    'El asiento no está cuadrado. Débitos: %s, Créditos: %s, Diferencia: %s',
                    number_format($totalDebito, 2),
                    number_format($totalCredito, 2),
                    number_format($diferencia, 2)
                )
            ];
        }

        return ['valido' => true, 'mensaje' => 'Asiento cuadrado correctamente'];
    }

    /**
     * Valida que las cuentas permitan movimiento y requisitos de tercero
     *
     * @param array $detalles Array de detalles del asiento
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarDetallesAsiento($detalles)
    {
        foreach ($detalles as $index => $detalle) {
            $cuenta = CuentaContable::on('tenant')->find($detalle['cuenta_contable_id']);

            if (!$cuenta) {
                return [
                    'valido' => false,
                    'mensaje' => "La cuenta del detalle " . ($index + 1) . " no existe"
                ];
            }

            // Validar que la cuenta permita movimientos
            if (!$cuenta->permite_movimiento) {
                return [
                    'valido' => false,
                    'mensaje' => "La cuenta {$cuenta->codigo} - {$cuenta->nombre} no permite movimientos directos. Use una cuenta de nivel inferior."
                ];
            }

            // Validar requisito de tercero
            if ($cuenta->requiere_tercero && empty($detalle['tercero_id'])) {
                return [
                    'valido' => false,
                    'mensaje' => "La cuenta {$cuenta->codigo} - {$cuenta->nombre} requiere un tercero asociado"
                ];
            }
        }

        return ['valido' => true, 'mensaje' => 'Detalles válidos'];
    }
}
