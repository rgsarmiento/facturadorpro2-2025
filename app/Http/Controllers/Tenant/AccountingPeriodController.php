<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AccountingPeriod;
use App\Models\Tenant\AsientoContable;
use App\Models\Tenant\DetalleAsientoContable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class AccountingPeriodController extends Controller
{
    public function index()
    {
        return view('tenant.periodos_contables.index');
    }

    public function records(Request $request)
    {
        try {
            $query = AccountingPeriod::on('tenant')
                ->with('usuarioCierre');

            // Filtros
            if ($request->year) {
                $query->where('year', $request->year);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $records = $query->orderBy('year', 'desc')
                           ->orderBy('month', 'desc')
                           ->get();

            return response()->json([
                'success' => true,
                'data' => $records
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener períodos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'year' => 'required|integer|min:2000|max:2100',
                'month' => 'required|integer|min:1|max:12',
            ]);

            // Verificar que no exista el período
            $exists = AccountingPeriod::on('tenant')
                ->where('year', $request->year)
                ->where('month', $request->month)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'El período ya existe'
                ], 422);
            }

            // Calcular fechas
            $startDate = \Carbon\Carbon::create($request->year, $request->month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $period = AccountingPeriod::on('tenant')->create([
                'year' => $request->year,
                'month' => $request->month,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'status' => 'open',
                'allow_modifications' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Período creado exitosamente',
                'data' => $period
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear período: ' . $e->getMessage()
            ], 500);
        }
    }

    public function close(Request $request, $id)
    {
        try {
            DB::connection('tenant')->beginTransaction();

            $period = AccountingPeriod::on('tenant')->findOrFail($id);

            if (!$period->isOpen()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El período ya está cerrado o bloqueado'
                ], 422);
            }

            // Verificar que todos los asientos estén confirmados
            $pendingVouchers = AsientoContable::on('tenant')
                ->where('period_id', $id)
                ->where('estado', 'BORRADOR')
                ->count();

            if ($pendingVouchers > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Hay {$pendingVouchers} asientos en borrador. Debe confirmarlos o eliminarlos antes de cerrar el período."
                ], 422);
            }

            $period->close(Auth::id(), $request->closing_notes);

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Período cerrado exitosamente',
                'data' => $period->fresh()
            ]);

        } catch (Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar período: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reopen($id)
    {
        try {
            DB::connection('tenant')->beginTransaction();

            $period = AccountingPeriod::on('tenant')->findOrFail($id);

            if ($period->isLocked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El período está bloqueado y no puede reabrirse'
                ], 422);
            }

            $period->reopen();

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Período reabierto exitosamente',
                'data' => $period->fresh()
            ]);

        } catch (Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al reabrir período: ' . $e->getMessage()
            ], 500);
        }
    }

    public function lock($id)
    {
        try {
            DB::connection('tenant')->beginTransaction();

            $period = AccountingPeriod::on('tenant')->findOrFail($id);

            if (!$period->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden bloquear períodos cerrados'
                ], 422);
            }

            $period->lock();

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Período bloqueado exitosamente',
                'data' => $period->fresh()
            ]);

        } catch (Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al bloquear período: ' . $e->getMessage()
            ], 500);
        }
    }

    public function current()
    {
        try {
            $period = AccountingPeriod::findOrCreateCurrent();

            return response()->json([
                'success' => true,
                'data' => $period
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener período actual: ' . $e->getMessage()
            ], 500);
        }
    }
}
