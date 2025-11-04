<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CuentaContable;
use App\Models\Tenant\AsientoContable;
use App\Models\Tenant\DetalleAsientoContable;
use App\Models\Tenant\AccountingPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingReportController extends Controller
{
    /**
     * Muestra la vista principal de reportes contables
     */
    public function index()
    {
        return view('tenant.reportes_contables.index');
    }

    /**
     * Balance de Prueba (Trial Balance)
     * Muestra todas las cuentas con sus saldos débito y crédito
     */
    public function trialBalance(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'level' => 'nullable|integer|min:1|max:10',
            ]);

            // Obtener todas las cuentas de movimiento
            $cuentasQuery = CuentaContable::on('tenant')
                ->where('permite_movimiento', true)
                ->where('activa', true);

            if ($request->level) {
                $cuentasQuery->where('nivel', '<=', $request->level);
            }

            $cuentas = $cuentasQuery->orderBy('codigo')->get();

            $report = [];
            $totalDebito = 0;
            $totalCredito = 0;
            $totalSaldoDebito = 0;
            $totalSaldoCredito = 0;

            foreach ($cuentas as $cuenta) {
                // Calcular movimientos del período
                $movimientos = DetalleAsientoContable::on('tenant')
                    ->whereHas('asientoContable', function($q) use ($request) {
                        $q->where('estado', 'CONFIRMADO')
                          ->whereBetween('fecha_asiento', [$request->start_date, $request->end_date]);
                    })
                    ->where('cuenta_contable_id', $cuenta->id)
                    ->selectRaw('SUM(debito) as total_debito, SUM(credito) as total_credito')
                    ->first();

                $debito = $movimientos->total_debito ?? 0;
                $credito = $movimientos->total_credito ?? 0;

                // Solo incluir cuentas con movimiento
                if ($debito > 0 || $credito > 0 || $cuenta->saldo_inicial != 0) {
                    $saldoInicial = $cuenta->saldo_inicial;

                    // Calcular saldo según naturaleza
                    if ($cuenta->naturaleza === 'debito') {
                        $saldo = $saldoInicial + $debito - $credito;
                        $saldoDebito = $saldo > 0 ? $saldo : 0;
                        $saldoCredito = $saldo < 0 ? abs($saldo) : 0;
                    } else {
                        $saldo = $saldoInicial + $credito - $debito;
                        $saldoDebito = $saldo < 0 ? abs($saldo) : 0;
                        $saldoCredito = $saldo > 0 ? $saldo : 0;
                    }

                    $report[] = [
                        'codigo' => $cuenta->codigo,
                        'nombre' => $cuenta->nombre,
                        'tipo_cuenta' => $cuenta->tipo_cuenta,
                        'naturaleza' => $cuenta->naturaleza,
                        'saldo_inicial' => $saldoInicial,
                        'debito' => $debito,
                        'credito' => $credito,
                        'saldo_debito' => $saldoDebito,
                        'saldo_credito' => $saldoCredito,
                    ];

                    $totalDebito += $debito;
                    $totalCredito += $credito;
                    $totalSaldoDebito += $saldoDebito;
                    $totalSaldoCredito += $saldoCredito;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'report' => $report,
                    'totals' => [
                        'total_debito' => $totalDebito,
                        'total_credito' => $totalCredito,
                        'total_saldo_debito' => $totalSaldoDebito,
                        'total_saldo_credito' => $totalSaldoCredito,
                        'is_balanced' => abs($totalDebito - $totalCredito) < 0.01,
                        'diferencia' => abs($totalDebito - $totalCredito),
                    ],
                    'filters' => [
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                        'level' => $request->level,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar balance de prueba: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Balance General (Balance Sheet)
     * Muestra activos, pasivos y patrimonio
     */
    public function balanceSheet(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date',
            ]);

            $activos = $this->getSaldosPorTipo('activo', $request->date);
            $pasivos = $this->getSaldosPorTipo('pasivo', $request->date);
            $patrimonio = $this->getSaldosPorTipo('patrimonio', $request->date);

            $totalActivos = collect($activos)->sum('saldo');
            $totalPasivos = collect($pasivos)->sum('saldo');
            $totalPatrimonio = collect($patrimonio)->sum('saldo');

            return response()->json([
                'success' => true,
                'data' => [
                    'activos' => $activos,
                    'pasivos' => $pasivos,
                    'patrimonio' => $patrimonio,
                    'totals' => [
                        'total_activos' => $totalActivos,
                        'total_pasivos' => $totalPasivos,
                        'total_patrimonio' => $totalPatrimonio,
                        'total_pasivos_patrimonio' => $totalPasivos + $totalPatrimonio,
                        'is_balanced' => abs($totalActivos - ($totalPasivos + $totalPatrimonio)) < 0.01,
                        'diferencia' => abs($totalActivos - ($totalPasivos + $totalPatrimonio)),
                    ],
                    'filters' => [
                        'date' => $request->date,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar balance general: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mayor Auxiliar (General Ledger)
     * Muestra todos los movimientos de una cuenta específica
     */
    public function generalLedger(Request $request)
    {
        try {
            $request->validate([
                'cuenta_contable_id' => 'required|exists:tenant.cuentas_contables,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $cuenta = CuentaContable::on('tenant')
                ->findOrFail($request->cuenta_contable_id);

            // Obtener movimientos
            $movimientos = DetalleAsientoContable::on('tenant')
                ->with(['asientoContable.tipoComprobante', 'tercero'])
                ->whereHas('asientoContable', function($q) use ($request) {
                    $q->where('estado', 'CONFIRMADO')
                      ->whereBetween('fecha_asiento', [$request->start_date, $request->end_date]);
                })
                ->where('cuenta_contable_id', $request->cuenta_contable_id)
                ->get()
                ->map(function($detalle) use ($cuenta) {
                    return [
                        'fecha' => $detalle->asientoContable->fecha_asiento,
                        'numero_comprobante' => $detalle->asientoContable->numero_comprobante,
                        'tipo_comprobante' => $detalle->asientoContable->tipoComprobante->nombre,
                        'concepto' => $detalle->concepto,
                        'tercero' => $detalle->tercero ? $detalle->tercero->name : null,
                        'debito' => $detalle->debito,
                        'credito' => $detalle->credito,
                    ];
                });

            // Calcular saldo acumulado
            $saldo = $cuenta->saldo_inicial;
            $movimientosConSaldo = [];

            foreach ($movimientos as $movimiento) {
                if ($cuenta->naturaleza === 'debito') {
                    $saldo = $saldo + $movimiento['debito'] - $movimiento['credito'];
                } else {
                    $saldo = $saldo + $movimiento['credito'] - $movimiento['debito'];
                }

                $movimiento['saldo'] = $saldo;
                $movimientosConSaldo[] = $movimiento;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'cuenta' => [
                        'codigo' => $cuenta->codigo,
                        'nombre' => $cuenta->nombre,
                        'tipo_cuenta' => $cuenta->tipo_cuenta,
                        'naturaleza' => $cuenta->naturaleza,
                        'saldo_inicial' => $cuenta->saldo_inicial,
                    ],
                    'movimientos' => $movimientosConSaldo,
                    'totals' => [
                        'total_debito' => $movimientos->sum('debito'),
                        'total_credito' => $movimientos->sum('credito'),
                        'saldo_final' => $saldo,
                    ],
                    'filters' => [
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar mayor auxiliar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Libro Diario (Journal Book)
     * Muestra todos los asientos contables del período
     */
    public function journalBook(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'tipo_comprobante_id' => 'nullable|exists:tenant.tipo_comprobantes_contables,id',
            ]);

            $query = AsientoContable::on('tenant')
                ->with(['tipoComprobante', 'detalles.cuentaContable', 'detalles.tercero'])
                ->where('estado', 'CONFIRMADO')
                ->whereBetween('fecha_asiento', [$request->start_date, $request->end_date]);

            if ($request->tipo_comprobante_id) {
                $query->where('tipo_comprobante_id', $request->tipo_comprobante_id);
            }

            $asientos = $query->orderBy('fecha_asiento')
                            ->orderBy('numero_comprobante')
                            ->get();

            $report = [];
            $totalDebito = 0;
            $totalCredito = 0;

            foreach ($asientos as $asiento) {
                $detalles = $asiento->detalles->map(function($detalle) {
                    return [
                        'cuenta_codigo' => $detalle->cuentaContable->codigo,
                        'cuenta_nombre' => $detalle->cuentaContable->nombre,
                        'tercero' => $detalle->tercero ? $detalle->tercero->name : null,
                        'concepto' => $detalle->concepto,
                        'debito' => $detalle->debito,
                        'credito' => $detalle->credito,
                    ];
                });

                $report[] = [
                    'fecha' => $asiento->fecha_asiento,
                    'numero_comprobante' => $asiento->numero_comprobante,
                    'tipo_comprobante' => $asiento->tipoComprobante->nombre,
                    'concepto' => $asiento->concepto,
                    'total_debito' => $asiento->total_debito,
                    'total_credito' => $asiento->total_credito,
                    'detalles' => $detalles,
                ];

                $totalDebito += $asiento->total_debito;
                $totalCredito += $asiento->total_credito;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'asientos' => $report,
                    'totals' => [
                        'total_debito' => $totalDebito,
                        'total_credito' => $totalCredito,
                        'cantidad_asientos' => count($report),
                        'is_balanced' => abs($totalDebito - $totalCredito) < 0.01,
                    ],
                    'filters' => [
                        'start_date' => $request->start_date,
                        'end_date' => $request->end_date,
                        'tipo_comprobante_id' => $request->tipo_comprobante_id,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar libro diario: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getSaldosPorTipo($tipoCuenta, $fecha)
    {
        $cuentas = CuentaContable::on('tenant')
            ->where('tipo_cuenta', $tipoCuenta)
            ->where('permite_movimiento', true)
            ->where('activa', true)
            ->orderBy('codigo')
            ->get();

        $result = [];

        foreach ($cuentas as $cuenta) {
            $movimientos = DetalleAsientoContable::on('tenant')
                ->whereHas('asientoContable', function($q) use ($fecha) {
                    $q->where('estado', 'CONFIRMADO')
                      ->where('fecha_asiento', '<=', $fecha);
                })
                ->where('cuenta_contable_id', $cuenta->id)
                ->selectRaw('SUM(debito) as total_debito, SUM(credito) as total_credito')
                ->first();

            $debito = $movimientos->total_debito ?? 0;
            $credito = $movimientos->total_credito ?? 0;

            // Calcular saldo según naturaleza
            if ($cuenta->naturaleza === 'debito') {
                $saldo = $cuenta->saldo_inicial + $debito - $credito;
            } else {
                $saldo = $cuenta->saldo_inicial + $credito - $debito;
            }

            // Solo incluir cuentas con saldo
            if (abs($saldo) > 0.01) {
                $result[] = [
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'nivel' => $cuenta->nivel,
                    'saldo' => $saldo,
                ];
            }
        }

        return $result;
    }
}
