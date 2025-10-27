<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AccountInitialBalance;
use App\Models\Tenant\CuentaContable;
use App\Models\Tenant\AccountingPeriod;
use App\Models\Tenant\AsientoContable;
use App\Models\Tenant\DetalleAsientoContable;
use App\Models\Tenant\TipoComprobanteContable;
use App\Models\Tenant\Person;
use App\Services\CuentaContableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class InitialBalanceController extends Controller
{
    protected $cuentaService;

    public function __construct(CuentaContableService $cuentaService)
    {
        $this->cuentaService = $cuentaService;
    }

    public function index()
    {
        return view('tenant.saldos_iniciales.index');
    }

    public function records(Request $request)
    {
        try {
            $query = AccountInitialBalance::on('tenant')
                ->with(['cuentaContable', 'tercero', 'period', 'asientoContable']);

            // Filtros
            if ($request->period_id) {
                $query->where('period_id', $request->period_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->cuenta_contable_id) {
                $query->where('cuenta_contable_id', $request->cuenta_contable_id);
            }

            $records = $query->orderBy('balance_date', 'desc')
                           ->orderBy('id', 'desc')
                           ->get();

            return response()->json([
                'success' => true,
                'data' => $records
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener saldos iniciales: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::connection('tenant')->beginTransaction();

            $request->validate([
                'balances' => 'required|array|min:1',
                'balances.*.cuenta_contable_id' => 'required|exists:tenant.cuentas_contables,id',
                'balances.*.person_id' => 'nullable|exists:tenant.persons,id',
                'balances.*.debito' => 'required|numeric|min:0',
                'balances.*.credito' => 'required|numeric|min:0',
                'period_id' => 'nullable|exists:tenant.accounting_periods,id',
                'balance_date' => 'required|date',
            ]);

            // Validar balance global
            $totalDebito = 0;
            $totalCredito = 0;

            foreach ($request->balances as $balance) {
                $totalDebito += $balance['debito'];
                $totalCredito += $balance['credito'];
            }

            $diferencia = abs($totalDebito - $totalCredito);

            if ($diferencia > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => "Los saldos iniciales no están balanceados. Diferencia: " . number_format($diferencia, 2),
                    'data' => [
                        'total_debito' => $totalDebito,
                        'total_credito' => $totalCredito,
                        'diferencia' => $diferencia
                    ]
                ], 422);
            }

            $created = [];
            $errors = [];

            foreach ($request->balances as $balanceData) {
                try {
                    // Validar cuenta
                    $cuenta = CuentaContable::on('tenant')->find($balanceData['cuenta_contable_id']);

                    if (!$cuenta->permite_movimiento) {
                        $errors[] = "La cuenta {$cuenta->codigo} no permite movimientos directos";
                        continue;
                    }

                    if ($cuenta->requiere_tercero && empty($balanceData['person_id'])) {
                        $errors[] = "La cuenta {$cuenta->codigo} requiere un tercero";
                        continue;
                    }

                    // Verificar que no exista duplicado
                    $exists = AccountInitialBalance::on('tenant')
                        ->where('cuenta_contable_id', $balanceData['cuenta_contable_id'])
                        ->where('person_id', $balanceData['person_id'] ?? null)
                        ->where('period_id', $request->period_id ?? null)
                        ->exists();

                    if ($exists) {
                        $errors[] = "Ya existe un saldo inicial para la cuenta {$cuenta->codigo}";
                        continue;
                    }

                    $balance = AccountInitialBalance::on('tenant')->create([
                        'cuenta_contable_id' => $balanceData['cuenta_contable_id'],
                        'person_id' => $balanceData['person_id'] ?? null,
                        'period_id' => $request->period_id,
                        'balance_date' => $request->balance_date,
                        'debito' => $balanceData['debito'],
                        'credito' => $balanceData['credito'],
                        'notes' => $balanceData['notes'] ?? null,
                        'created_by' => Auth::id(),
                        'status' => 'draft',
                    ]);

                    $created[] = $balance;

                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            DB::connection('tenant')->commit();

            $message = count($created) . ' saldos iniciales creados';
            if (count($errors) > 0) {
                $message .= '. ' . count($errors) . ' errores: ' . implode(', ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'created' => count($created),
                    'errors' => $errors,
                    'balances' => $created
                ]
            ]);

        } catch (Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear saldos iniciales: ' . $e->getMessage()
            ], 500);
        }
    }

    public function post(Request $request)
    {
        try {
            DB::connection('tenant')->beginTransaction();

            $request->validate([
                'period_id' => 'nullable|exists:tenant.accounting_periods,id',
                'balance_ids' => 'required|array|min:1',
            ]);

            // Obtener saldos en borrador
            $balances = AccountInitialBalance::on('tenant')
                ->whereIn('id', $request->balance_ids)
                ->where('status', 'draft')
                ->with('cuentaContable')
                ->get();

            if ($balances->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay saldos iniciales en borrador para contabilizar'
                ], 422);
            }

            // Validar balance
            $totalDebito = $balances->sum('debito');
            $totalCredito = $balances->sum('credito');
            $diferencia = abs($totalDebito - $totalCredito);

            if ($diferencia > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => "Los saldos no están balanceados. Diferencia: " . number_format($diferencia, 2)
                ], 422);
            }

            // Buscar tipo de comprobante "Saldos Iniciales"
            $tipoComprobante = TipoComprobanteContable::on('tenant')
                ->where('codigo', '24')
                ->orWhere('nombre', 'Saldos Iniciales')
                ->first();

            if (!$tipoComprobante) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el tipo de comprobante de Saldos Iniciales'
                ], 422);
            }

            // Obtener próximo consecutivo
            $ultimoConsecutivo = AsientoContable::on('tenant')
                ->where('tipo_comprobante_id', $tipoComprobante->id)
                ->max('consecutivo') ?? 0;

            $nuevoConsecutivo = $ultimoConsecutivo + 1;

            // Crear asiento de apertura
            $asiento = AsientoContable::on('tenant')->create([
                'tipo_comprobante_id' => $tipoComprobante->id,
                'numero_comprobante' => $tipoComprobante->prefijo . '-' . $nuevoConsecutivo,
                'consecutivo' => $nuevoConsecutivo,
                'fecha_asiento' => $balances->first()->balance_date,
                'period_id' => $request->period_id,
                'concepto' => 'Asiento de apertura - Saldos iniciales',
                'total_debito' => $totalDebito,
                'total_credito' => $totalCredito,
                'tipo_origen' => 'AUTOMATICO',
                'modulo_origen' => 'MANUAL',
                'estado' => 'CONFIRMADO',
                'usuario_creacion' => Auth::id(),
                'fecha_creacion' => now(),
                'usuario_confirmacion' => Auth::id(),
                'fecha_confirmacion' => now(),
            ]);

            // Crear detalles del asiento
            $orden = 1;
            foreach ($balances as $balance) {
                DetalleAsientoContable::on('tenant')->create([
                    'asiento_contable_id' => $asiento->id,
                    'cuenta_contable_id' => $balance->cuenta_contable_id,
                    'person_id' => $balance->person_id,
                    'debito' => $balance->debito,
                    'credito' => $balance->credito,
                    'concepto' => 'Saldo inicial - ' . $balance->cuentaContable->nombre,
                    'orden' => $orden++,
                ]);

                // Actualizar saldo inicial
                $balance->update([
                    'status' => 'posted',
                    'is_posted' => true,
                    'posted_at' => now(),
                    'posted_by' => Auth::id(),
                    'asiento_contable_id' => $asiento->id,
                ]);
            }

            // Actualizar saldos de cuentas
            $this->cuentaService->actualizarSaldosPorAsiento($asiento->id, 'crear');

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Saldos iniciales contabilizados exitosamente',
                'data' => [
                    'asiento' => $asiento->fresh(['detalles.cuentaContable']),
                    'balances_posted' => $balances->count()
                ]
            ]);

        } catch (Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al contabilizar saldos iniciales: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::connection('tenant')->beginTransaction();

            $balance = AccountInitialBalance::on('tenant')->findOrFail($id);

            if ($balance->isPosted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un saldo inicial ya contabilizado'
                ], 422);
            }

            $balance->delete();

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Saldo inicial eliminado exitosamente'
            ]);

        } catch (Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar saldo inicial: ' . $e->getMessage()
            ], 500);
        }
    }

    public function validateBalances(Request $request)
    {
        try {
            $request->validate([
                'balances' => 'required|array|min:1',
            ]);

            $totalDebito = 0;
            $totalCredito = 0;
            $errors = [];

            foreach ($request->balances as $index => $balance) {
                $totalDebito += $balance['debito'] ?? 0;
                $totalCredito += $balance['credito'] ?? 0;

                // Validar cuenta
                if (isset($balance['cuenta_contable_id'])) {
                    $cuenta = CuentaContable::on('tenant')->find($balance['cuenta_contable_id']);

                    if ($cuenta) {
                        if (!$cuenta->permite_movimiento) {
                            $errors[] = "Línea " . ($index + 1) . ": La cuenta {$cuenta->codigo} no permite movimientos";
                        }

                        if ($cuenta->requiere_tercero && empty($balance['person_id'])) {
                            $errors[] = "Línea " . ($index + 1) . ": La cuenta {$cuenta->codigo} requiere un tercero";
                        }
                    }
                }

                // Validar que no haya débito y crédito simultáneos
                if (($balance['debito'] ?? 0) > 0 && ($balance['credito'] ?? 0) > 0) {
                    $errors[] = "Línea " . ($index + 1) . ": No puede haber débito y crédito simultáneamente";
                }
            }

            $diferencia = abs($totalDebito - $totalCredito);
            $isBalanced = $diferencia < 0.01;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_debito' => $totalDebito,
                    'total_credito' => $totalCredito,
                    'diferencia' => $diferencia,
                    'is_balanced' => $isBalanced,
                    'errors' => $errors,
                    'has_errors' => count($errors) > 0,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar saldos: ' . $e->getMessage()
            ], 500);
        }
    }
}
