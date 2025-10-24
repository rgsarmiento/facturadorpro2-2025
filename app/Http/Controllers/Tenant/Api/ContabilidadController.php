<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CuentaContable;
use App\Models\Tenant\AsientoContable;
use App\Models\Tenant\DetalleAsientoContable;
use App\Models\Tenant\TipoComprobanteContable;
use App\Models\Tenant\Person;
use Modules\Factcolombia1\Models\Tenant\{
    TypeIdentityDocument,
    Country,
    Department,
    City,
    TypePerson,
    TypeRegime
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;

class ContabilidadController extends Controller
{
    /**
     * ============================================
     * PLAN ÚNICO DE CUENTAS (PUC) - ENDPOINTS
     * ============================================
     */

    /**
     * Listar todas las cuentas contables
     * GET /api/contabilidad/cuentas
     */
    public function getCuentas(Request $request)
    {
        try {
            $query = CuentaContable::on('tenant');

            // Filtros opcionales
            if ($request->has('activa')) {
                $query->where('activa', $request->activa);
            }

            if ($request->has('tipo_cuenta')) {
                $query->where('tipo_cuenta', $request->tipo_cuenta);
            }

            if ($request->has('nivel')) {
                $query->where('nivel', $request->nivel);
            }

            if ($request->has('permite_movimiento')) {
                $query->where('permite_movimiento', $request->permite_movimiento);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('codigo', 'like', "%{$search}%")
                      ->orWhere('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $cuentas = $query->orderBy('codigo')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $cuentas->items(),
                'pagination' => [
                    'current_page' => $cuentas->currentPage(),
                    'last_page' => $cuentas->lastPage(),
                    'per_page' => $cuentas->perPage(),
                    'total' => $cuentas->total(),
                    'from' => $cuentas->firstItem(),
                    'to' => $cuentas->lastItem(),
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cuentas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cuenta contable por código
     * GET /api/contabilidad/cuentas-contables/{codigo}
     */
    public function getCuenta($codigo)
    {
        try {
            $cuenta = CuentaContable::on('tenant')
                ->with(['cuentaPadre', 'cuentasHijas'])
                ->where('codigo', $codigo)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $cuenta
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta no encontrada'
            ], 404);
        }
    }

    /**
     * Crear nueva cuenta contable
     * POST /api/contabilidad/cuentas
     */
    public function storeCuenta(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'codigo' => 'required|string|max:20|unique:tenant.cuentas_contables,codigo',
                'nombre' => 'required|string|max:255',
                'tipo_cuenta' => 'required|in:activo,pasivo,patrimonio,ingreso,gasto,costo',
                'naturaleza' => 'required|in:debito,credito',
                'cuenta_padre_codigo' => 'nullable|string|exists:tenant.cuentas_contables,codigo',
                'descripcion' => 'nullable|string',
                'activa' => 'boolean',
                'permite_movimiento' => 'boolean',
                'requiere_tercero' => 'boolean',
                'saldo_inicial' => 'nullable|numeric',
                'codigo_niif' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $data = $request->all();

            // Determinar cuenta_padre_id y nivel automáticamente
            $cuenta_padre_id = null;
            $nivel = 1;

            if ($request->has('cuenta_padre_codigo') && $request->cuenta_padre_codigo) {
                $cuentaPadre = CuentaContable::on('tenant')
                    ->where('codigo', $request->cuenta_padre_codigo)
                    ->first();

                if ($cuentaPadre) {
                    $cuenta_padre_id = $cuentaPadre->id;
                    $nivel = $cuentaPadre->nivel + 1;
                }
            } else {
                // Si no tiene padre, calcular nivel por longitud del código
                $nivel = $this->calcularNivelPorCodigo($request->codigo);
            }

            $data['cuenta_padre_id'] = $cuenta_padre_id;
            $data['nivel'] = $nivel;
            unset($data['cuenta_padre_codigo']); // Remover este campo temporal

            $cuenta = CuentaContable::on('tenant')->create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta creada exitosamente',
                'data' => $cuenta
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear cuenta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular el nivel de una cuenta basado en su código
     * Ejemplo: "1" = nivel 1, "11" = nivel 2, "1105" = nivel 3, "110505" = nivel 4
     */
    private function calcularNivelPorCodigo($codigo)
    {
        $longitud = strlen($codigo);

        // Estrategia común: cada 2 dígitos adicionales = 1 nivel más
        if ($longitud <= 1) return 1;
        if ($longitud <= 2) return 2;
        if ($longitud <= 4) return 3;
        if ($longitud <= 6) return 4;
        if ($longitud <= 8) return 5;

        return ceil($longitud / 2);
    }

    /**
     * Actualizar cuenta contable
     * PUT /api/contabilidad/cuentas-contables/{codigo}
     */
    public function updateCuenta(Request $request, $codigo)
    {
        try {
            $cuenta = CuentaContable::on('tenant')->where('codigo', $codigo)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'codigo' => 'required|string|max:20|unique:tenant.cuentas_contables,codigo,' . $cuenta->id,
                'nombre' => 'required|string|max:255',
                'tipo_cuenta' => 'required|in:activo,pasivo,patrimonio,ingreso,gasto,costo',
                'naturaleza' => 'required|in:debito,credito',
                'cuenta_padre_codigo' => 'nullable|string|exists:tenant.cuentas_contables,codigo',
                'descripcion' => 'nullable|string',
                'activa' => 'boolean',
                'permite_movimiento' => 'boolean',
                'requiere_tercero' => 'boolean',
                'saldo_inicial' => 'nullable|numeric',
                'codigo_niif' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $data = $request->all();

            // Determinar cuenta_padre_id y nivel automáticamente
            $cuenta_padre_id = null;
            $nivel = 1;

            if ($request->has('cuenta_padre_codigo') && $request->cuenta_padre_codigo) {
                $cuentaPadre = CuentaContable::on('tenant')
                    ->where('codigo', $request->cuenta_padre_codigo)
                    ->first();

                if ($cuentaPadre) {
                    $cuenta_padre_id = $cuentaPadre->id;
                    $nivel = $cuentaPadre->nivel + 1;
                }
            } else {
                // Si no tiene padre, calcular nivel por longitud del código
                $nivel = $this->calcularNivelPorCodigo($data['codigo']);
            }

            $data['cuenta_padre_id'] = $cuenta_padre_id;
            $data['nivel'] = $nivel;
            unset($data['cuenta_padre_codigo']); // Remover este campo temporal

            $cuenta->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta actualizada exitosamente',
                'data' => $cuenta->fresh()
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cuenta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cuenta contable
     * DELETE /api/contabilidad/cuentas-contables/{codigo}
     */
    public function deleteCuenta($codigo)
    {
        try {
            $cuenta = CuentaContable::on('tenant')->where('codigo', $codigo)->firstOrFail();

            // Verificar si tiene cuentas hijas
            if ($cuenta->cuentasHijas()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una cuenta que tiene subcuentas'
                ], 422);
            }

            // Verificar si tiene movimientos
            if ($cuenta->detallesAsientos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una cuenta con movimientos contables'
                ], 422);
            }

            $cuenta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta eliminada exitosamente'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar cuenta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener árbol jerárquico de cuentas
     * GET /api/contabilidad/cuentas/tree
     */
    public function getCuentasTree(Request $request)
    {
        try {
            $cuentasPadre = CuentaContable::on('tenant')
                ->whereNull('cuenta_padre_id')
                ->with('cuentasHijas')
                ->orderBy('codigo')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $cuentasPadre
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener árbol de cuentas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================
     * ASIENTOS CONTABLES - ENDPOINTS
     * ============================================
     */

    /**
     * Listar asientos contables
     * GET /api/contabilidad/asientos
     */
    public function getAsientos(Request $request)
    {
        try {
            $query = AsientoContable::on('tenant')
                ->with(['tipoComprobante', 'usuarioCreacion']);

            // Incluir eliminados si se solicita
            if ($request->get('incluir_eliminados', false)) {
                $query->withTrashed();
            }

            // Filtros
            if ($request->has('fecha_inicio')) {
                $query->where('fecha_asiento', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin')) {
                $query->where('fecha_asiento', '<=', $request->fecha_fin);
            }

            if ($request->has('tipo_comprobante_id')) {
                $query->where('tipo_comprobante_id', $request->tipo_comprobante_id);
            }

            if ($request->has('estado')) {
                if (strtoupper($request->estado) === 'ANULADO') {
                    $query->where(function ($q) {
                        $q->where('estado', 'ANULADO')
                          ->orWhereNotNull('deleted_at');
                    });
                } else {
                    $query->where('estado', $request->estado);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_comprobante', 'like', "%{$search}%")
                      ->orWhere('concepto', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $asientos = $query->orderBy('fecha_asiento', 'desc')
                             ->orderBy('id', 'desc')
                             ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $asientos->items(),
                'pagination' => [
                    'current_page' => $asientos->currentPage(),
                    'last_page' => $asientos->lastPage(),
                    'per_page' => $asientos->perPage(),
                    'total' => $asientos->total(),
                    'from' => $asientos->firstItem(),
                    'to' => $asientos->lastItem(),
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener asientos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener asiento contable por ID
     * GET /api/contabilidad/asientos/{id}
     */
    public function getAsiento($numero_comprobante)
    {
        try {
            $asiento = AsientoContable::on('tenant')
                ->withTrashed()
                ->with([
                    'detalles.cuentaContable',
                    'detalles.tercero',
                    'tipoComprobante',
                    'usuarioCreacion',
                    'adjuntos'
                ])
                ->where('numero_comprobante', $numero_comprobante)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $asiento
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Asiento no encontrado'
            ], 404);
        }
    }

    /**
     * Crear asiento contable
     * POST /api/contabilidad/asientos
     */
    public function storeAsiento(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_asiento' => 'required|date',
                'tipo_comprobante_id' => 'required|exists:tenant.tipo_comprobantes_contables,id',
                'concepto' => 'required|string|max:500',
                'detalles' => 'required|array|min:2',
                'detalles.*.cuenta_contable_codigo' => 'required|exists:tenant.cuentas_contables,codigo',
                'detalles.*.concepto' => 'required|string|max:255',
                'detalles.*.debito' => 'required|numeric|min:0',
                'detalles.*.credito' => 'required|numeric|min:0',
                'detalles.*.tercero_number' => 'nullable|exists:tenant.persons,number',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Validar que todos los detalles tengan concepto
            foreach ($request->detalles as $index => $detalle) {
                if (empty($detalle['concepto']) || trim($detalle['concepto']) === '') {
                    return response()->json([
                        'success' => false,
                        'message' => "El detalle " . ($index + 1) . " debe tener un concepto válido"
                    ], 422);
                }
            }

            // Obtener tipo de comprobante y generar número
            $tipoComprobante = TipoComprobanteContable::on('tenant')
                ->lockForUpdate()
                ->findOrFail($request->tipo_comprobante_id);

            $consecutivo = (int)($tipoComprobante->consecutivo_actual ?? 0) + 1;
            $numeroComprobante = (string)($tipoComprobante->prefijo ?? '') . $consecutivo;

            // Calcular totales
            $totalDebito = 0;
            $totalCredito = 0;
            foreach ($request->detalles as $detalle) {
                $totalDebito += floatval($detalle['debito'] ?? 0);
                $totalCredito += floatval($detalle['credito'] ?? 0);
            }

            // Crear asiento
            $asiento = AsientoContable::on('tenant')->create([
                'fecha_asiento' => $request->fecha_asiento,
                'tipo_comprobante_id' => $request->tipo_comprobante_id,
                'numero_comprobante' => $numeroComprobante,
                'consecutivo' => $consecutivo,
                'concepto' => $request->concepto,
                'total_debito' => $totalDebito,
                'total_credito' => $totalCredito,
                'estado' => 'BORRADOR',
                'usuario_creacion' => Auth::id(),
                'fecha_creacion' => now(),
            ]);

            // Actualizar consecutivo del tipo
            $tipoComprobante->consecutivo_actual = $consecutivo;
            $tipoComprobante->save();

            // Crear detalles
            $orden = 1;
            foreach ($request->detalles as $detalle) {
                // Buscar cuenta contable por código
                $cuentaContable = CuentaContable::on('tenant')
                    ->where('codigo', $detalle['cuenta_contable_codigo'])
                    ->first();

                // Buscar tercero por number si está presente
                $personId = null;
                if (!empty($detalle['tercero_number'])) {
                    $person = Person::on('tenant')
                        ->where('number', $detalle['tercero_number'])
                        ->first();
                    $personId = $person ? $person->id : null;
                }

                DetalleAsientoContable::on('tenant')->create([
                    'asiento_contable_id' => $asiento->id,
                    'cuenta_contable_id' => $cuentaContable->id,
                    'person_id' => $personId,
                    'concepto' => trim($detalle['concepto']),
                    'debito' => floatval($detalle['debito'] ?? 0),
                    'credito' => floatval($detalle['credito'] ?? 0),
                    'orden' => $orden++,
                ]);
            }

            DB::commit();

            // Recargar con relaciones
            $asiento->load(['detalles.cuentaContable', 'detalles.tercero', 'tipoComprobante']);

            return response()->json([
                'success' => true,
                'message' => 'Asiento creado exitosamente',
                'data' => $asiento
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear asiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar asiento contable (solo en borrador)
     * PUT /api/contabilidad/asientos/{numero_comprobante}
     */
    public function updateAsiento(Request $request, $numero_comprobante)
    {
        try {
            $asiento = AsientoContable::on('tenant')
                ->where('numero_comprobante', $numero_comprobante)
                ->firstOrFail();

            // Solo borrador puede editarse
            if (strtoupper($asiento->estado) !== 'BORRADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar asientos en estado BORRADOR'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'fecha_asiento' => 'required|date',
                'concepto' => 'required|string|max:500',
                'detalles' => 'required|array|min:2',
                'detalles.*.cuenta_contable_codigo' => 'required|exists:tenant.cuentas_contables,codigo',
                'detalles.*.concepto' => 'required|string|max:255',
                'detalles.*.debito' => 'required|numeric|min:0',
                'detalles.*.credito' => 'required|numeric|min:0',
                'detalles.*.tercero_number' => 'nullable|exists:tenant.persons,number',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar asiento
            $asiento->update([
                'fecha_asiento' => $request->fecha_asiento,
                'concepto' => $request->concepto,
                'fecha_modificacion' => now(),
            ]);

            // Eliminar y recrear detalles
            $asiento->detalles()->delete();

            $totalDebito = 0;
            $totalCredito = 0;
            $orden = 1;

            foreach ($request->detalles as $detalle) {
                $debitoValue = floatval($detalle['debito'] ?? 0);
                $creditoValue = floatval($detalle['credito'] ?? 0);

                $totalDebito += $debitoValue;
                $totalCredito += $creditoValue;

                // Buscar cuenta contable por código
                $cuentaContable = CuentaContable::on('tenant')
                    ->where('codigo', $detalle['cuenta_contable_codigo'])
                    ->first();

                // Buscar tercero por number si está presente
                $personId = null;
                if (!empty($detalle['tercero_number'])) {
                    $person = Person::on('tenant')
                        ->where('number', $detalle['tercero_number'])
                        ->first();
                    $personId = $person ? $person->id : null;
                }

                DetalleAsientoContable::on('tenant')->create([
                    'asiento_contable_id' => $asiento->id,
                    'cuenta_contable_id' => $cuentaContable->id,
                    'person_id' => $personId,
                    'concepto' => trim($detalle['concepto']),
                    'debito' => $debitoValue,
                    'credito' => $creditoValue,
                    'orden' => $orden++,
                ]);
            }

            // Actualizar totales
            $asiento->update([
                'total_debito' => $totalDebito,
                'total_credito' => $totalCredito,
            ]);

            DB::commit();

            $asiento->load(['detalles.cuentaContable', 'detalles.tercero', 'tipoComprobante']);

            return response()->json([
                'success' => true,
                'message' => 'Asiento actualizado exitosamente',
                'data' => $asiento
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar asiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar/Aprobar asiento contable
     * POST /api/contabilidad/asientos/{numero_comprobante}/confirmar
     */
    public function confirmarAsiento($numero_comprobante)
    {
        try {
            $asiento = AsientoContable::on('tenant')
                ->where('numero_comprobante', $numero_comprobante)
                ->firstOrFail();

            if (strtoupper($asiento->estado) !== 'BORRADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar asientos en estado BORRADOR'
                ], 422);
            }

            // Validar balance
            $asiento->load('detalles.cuentaContable');
            $asiento->calcularTotales();

            if (!$asiento->estaBalanceado()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El asiento no está balanceado. Débito y Crédito deben ser iguales'
                ], 422);
            }

            $asiento->confirmar(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Asiento confirmado exitosamente',
                'data' => $asiento->fresh()
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar asiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar asiento contable (solo borrador)
     * DELETE /api/contabilidad/asientos/{numero_comprobante}
     */
    public function deleteAsiento($numero_comprobante)
    {
        try {
            $asiento = AsientoContable::on('tenant')
                ->where('numero_comprobante', $numero_comprobante)
                ->firstOrFail();

            if (strtoupper($asiento->estado) !== 'BORRADOR') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden eliminar asientos en estado BORRADOR'
                ], 422);
            }

            $asiento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asiento eliminado exitosamente'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar asiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================
     * CATÁLOGOS Y DATOS AUXILIARES
     * ============================================
     */

    /**
     * Obtener tipos de comprobantes
     * GET /api/contabilidad/tipos-comprobantes
     */
    public function getTiposComprobantes()
    {
        try {
            $tipos = TipoComprobanteContable::on('tenant')
                ->where('estado', 'ACTIVO')
                ->orderBy('codigo')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de comprobantes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener terceros/personas
     * GET /api/contabilidad/terceros
     */
    public function getTerceros(Request $request)
    {
        try {
            $query = Person::on('tenant')->where('enabled', true);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('number', 'like', "%{$search}%");
                });
            }

            $terceros = $query->orderBy('name')->get(['id', 'name', 'number']);

            return response()->json([
                'success' => true,
                'data' => $terceros
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener terceros: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener próximo consecutivo para un tipo de comprobante
     * GET /api/contabilidad/proximo-consecutivo/{tipo_comprobante_id}
     */
    public function getProximoConsecutivo($tipoComprobanteId)
    {
        try {
            $tipoComprobante = TipoComprobanteContable::on('tenant')->findOrFail($tipoComprobanteId);

            $proximoNumero = (int)($tipoComprobante->consecutivo_actual ?? 0) + 1;
            $formateado = (string)($tipoComprobante->prefijo ?? '') . $proximoNumero;

            return response()->json([
                'success' => true,
                'data' => [
                    'proximo_consecutivo' => $proximoNumero,
                    'numero_formateado' => $formateado,
                    'tipo_comprobante' => $tipoComprobante->nombre ?? $tipoComprobante->descripcion ?? ''
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de comprobante no encontrado'
            ], 404);
        }
    }

    /**
     * Crear nuevo tercero
     * POST /api/contabilidad/terceros
     */
    public function storeTercero(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:customers,suppliers,both',
                'identity_document_type_id' => 'required|exists:tenant.co_type_identity_documents,id',
                'number' => 'required|string|max:20|unique:tenant.persons,number',
                'name' => 'required|string|max:255',
                'trade_name' => 'nullable|string|max:255',
                'country_id' => 'nullable|exists:tenant.co_countries,id',
                'department_id' => 'nullable|exists:tenant.co_departments,id',
                'city_id' => 'nullable|exists:tenant.co_cities,id',
                'address' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'telephone' => 'nullable|string|max:50',
                'type_person_id' => 'nullable|exists:tenant.co_type_people,id',
                'type_regime_id' => 'nullable|exists:tenant.co_type_regimes,id',
                'code' => 'nullable|string|max:50',
                'dv' => 'nullable|string|max:2',
                'contact_name' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'postal_code' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tercero = Person::on('tenant')->create(array_merge(
                $request->all(),
                ['enabled' => true]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Tercero creado exitosamente',
                'data' => $tercero
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear tercero: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar tercero
     * PUT /api/contabilidad/terceros/{number}
     */
    public function updateTercero(Request $request, $number)
    {
        try {
            $tercero = Person::on('tenant')
                ->where('number', $number)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'type' => 'required|in:customers,suppliers,both',
                'identity_document_type_id' => 'required|exists:tenant.co_type_identity_documents,id',
                'number' => 'required|string|max:20|unique:tenant.persons,number,' . $tercero->id,
                'name' => 'required|string|max:255',
                'trade_name' => 'nullable|string|max:255',
                'country_id' => 'nullable|exists:tenant.co_countries,id',
                'department_id' => 'nullable|exists:tenant.co_departments,id',
                'city_id' => 'nullable|exists:tenant.co_cities,id',
                'address' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'telephone' => 'nullable|string|max:50',
                'type_person_id' => 'nullable|exists:tenant.co_type_people,id',
                'type_regime_id' => 'nullable|exists:tenant.co_type_regimes,id',
                'code' => 'nullable|string|max:50',
                'dv' => 'nullable|string|max:2',
                'contact_name' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'postal_code' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tercero->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Tercero actualizado exitosamente',
                'data' => $tercero->fresh()
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar tercero: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar tercero
     * DELETE /api/contabilidad/terceros/{number}
     */
    public function deleteTercero($number)
    {
        try {
            $tercero = Person::on('tenant')
                ->where('number', $number)
                ->firstOrFail();

            // Validar si tiene movimientos contables
            $tieneMovimientos = DetalleAsientoContable::on('tenant')
                ->where('person_id', $tercero->id)
                ->exists();

            if ($tieneMovimientos) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el tercero porque tiene movimientos contables asociados'
                ], 422);
            }

            $tercero->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tercero eliminado exitosamente'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar tercero: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================
     * CATÁLOGOS PARA TERCEROS - ENDPOINTS
     * ============================================
     */

    /**
     * Listar tipos de documentos de identidad
     * GET /api/contabilidad/tipos-documentos-identidad
     */
    public function getTiposDocumentosIdentidad()
    {
        try {
            $tipos = TypeIdentityDocument::on('tenant')
                ->orderBy('name')
                ->get(['id', 'name as description', 'code']);

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de documentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar países
     * GET /api/contabilidad/paises
     */
    public function getPaises()
    {
        try {
            $paises = Country::on('tenant')
                ->orderBy('name')
                ->get(['id', 'name as description', 'code']);

            return response()->json([
                'success' => true,
                'data' => $paises
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener países: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar departamentos
     * GET /api/contabilidad/departamentos
     * GET /api/contabilidad/departamentos?country_id=1
     */
    public function getDepartamentos(Request $request)
    {
        try {
            $query = Department::on('tenant');

            if ($request->has('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            $departamentos = $query->orderBy('name')
                ->get(['id', 'name as description', 'country_id']);

            return response()->json([
                'success' => true,
                'data' => $departamentos
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener departamentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar ciudades
     * GET /api/contabilidad/ciudades
     * GET /api/contabilidad/ciudades?department_id=5
     */
    public function getCiudades(Request $request)
    {
        try {
            $query = City::on('tenant');

            if ($request->has('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            $ciudades = $query->orderBy('name')
                ->get(['id', 'name as description', 'department_id']);

            return response()->json([
                'success' => true,
                'data' => $ciudades
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ciudades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar tipos de persona
     * GET /api/contabilidad/tipos-persona
     */
    public function getTiposPersona()
    {
        try {
            $tipos = TypePerson::on('tenant')
                ->orderBy('name')
                ->get(['id', 'name as description']);

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de persona: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar tipos de régimen
     * GET /api/contabilidad/tipos-regimen
     */
    public function getTiposRegimen()
    {
        try {
            $tipos = TypeRegime::on('tenant')
                ->orderBy('name')
                ->get(['id', 'name as description', 'code']);

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de régimen: ' . $e->getMessage()
            ], 500);
        }
    }
}
