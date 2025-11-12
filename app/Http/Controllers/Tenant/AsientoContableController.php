<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AsientoContable;
use App\Models\Tenant\TipoComprobanteContable;
use App\Models\Tenant\DetalleAsientoContable;
use App\Models\Tenant\CuentaContable;
use App\Models\Tenant\AsientoAdjunto;
use App\Models\Tenant\Person;
use App\Services\CuentaContableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Exception;
use Hyn\Tenancy\Contracts\CurrentHostname;
use Hyn\Tenancy\Database\Connection;

class AsientoContableController extends Controller
{
    protected $cuentaService;

    public function __construct(CuentaContableService $cuentaService)
    {
        $this->cuentaService = $cuentaService;
    }
    public function index()
    {
        return view('tenant.asientos_contables.index');
    }

    public function create()
    {
        return view('tenant.asientos_contables.form_clean');
    }

    public function show($id)
    {
        $this->ensureTenantConnection();

        $asiento = AsientoContable::on('tenant')->withTrashed()->with(['detalles.cuentaContable', 'detalles.tercero', 'tipoComprobante', 'adjuntos'])
            ->findOrFail($id);

        return view('tenant.asientos_contables.show', compact('asiento'));
    }

    public function edit($id)
    {
        $this->ensureTenantConnection();

        $asiento = AsientoContable::on('tenant')->with([
            'detalles.cuentaContable',
            'detalles.tercero',
            'tipoComprobante',
            'adjuntos'
        ])->findOrFail($id);

        return view('tenant.asientos_contables.form', compact('asiento'));
    }

    public function records(Request $request)
    {
        try {
            // Verificar que la conexión tenant esté configurada
            $this->ensureTenantConnection();



            // Incluir eliminados lógicamente para que aparezcan en el listado
            $records = AsientoContable::on('tenant')
                ->withTrashed()
                ->with(['tipoComprobante', 'usuarioCreacion'])
                ->when($request->fecha_inicio, function ($query, $fecha) {
                    return $query->where('fecha_asiento', '>=', $fecha);
                })
                ->when($request->fecha_fin, function ($query, $fecha) {
                    return $query->where('fecha_asiento', '<=', $fecha);
                })
                ->when($request->tipo_comprobante_id, function ($query, $tipo) {
                    return $query->where('tipo_comprobante_id', $tipo);
                })
                ->when($request->estado, function ($query, $estado) {
                    if (strtoupper($estado) === 'ANULADO') {
                        // Incluir registros propiamente anulados y también eliminados lógicamente
                        return $query->where(function ($q) {
                            $q->where('estado', 'ANULADO')
                              ->orWhereNotNull('deleted_at');
                        });
                    }
                    return $query->where('estado', $estado);
                })
                ->when($request->search, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('numero_comprobante', 'like', "%{$search}%")
                          ->orWhere('concepto', 'like', "%{$search}%");
                    });
                })
                ->orderBy('fecha_asiento', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(15);



        } catch (\Exception $e) {

            return [
                'success' => false,
                'message' => 'Error al cargar registros: ' . $e->getMessage()
            ];
        }

        return [
            'success' => true,
            'data' => [
                'records' => $records->items(),
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'last_page' => $records->lastPage(),
                    'per_page' => $records->perPage(),
                    'total' => $records->total(),
                    'from' => $records->firstItem(),
                    'to' => $records->lastItem(),
                ]
            ]
        ];
    }

    public function store(Request $request)
    {
        try {
            $this->ensureTenantConnection();

            DB::beginTransaction();

            // Validar el tipo de comprobante y bloquear la fila para manejo seguro del consecutivo
            $tipoComprobante = TipoComprobanteContable::on('tenant')
                ->lockForUpdate()
                ->findOrFail($request->tipo_comprobante_id);

            // Validar si es comprobante de "Saldos Iniciales" (código 24)
            if ($tipoComprobante->codigo === '24') {
                // Verificar si ya existe un comprobante de saldos iniciales (en cualquier estado)
                $existeSaldosIniciales = AsientoContable::on('tenant')
                    ->where('tipo_comprobante_id', $tipoComprobante->id)
                    ->whereIn('estado', ['BORRADOR', 'CONFIRMADO'])
                    ->exists();

                if ($existeSaldosIniciales) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'Ya existe un comprobante de Saldos Iniciales. Solo puede haber uno por empresa.'
                    ];
                }
            }

            // Validar partida doble
            if ($request->has('detalles')) {
                // Si es comprobante de Saldos Iniciales (código 24), validar que no haya cuentas que requieren tercero
                if ($tipoComprobante->codigo === '24') {
                    foreach ($request->detalles as $detalle) {
                        $cuenta = CuentaContable::on('tenant')->find($detalle['cuenta_contable_id']);
                        if ($cuenta && $cuenta->requiere_tercero) {
                            DB::rollBack();
                            return [
                                'success' => false,
                                'message' => "La cuenta {$cuenta->codigo} requiere tercero y no puede ser usada en comprobantes de Saldos Iniciales."
                            ];
                        }
                    }
                }

                $validacionPartidaDoble = $this->cuentaService->validarPartidaDoble($request->detalles);
                if (!$validacionPartidaDoble['valido']) {
                    return [
                        'success' => false,
                        'message' => $validacionPartidaDoble['mensaje']
                    ];
                }

                // Validar detalles del asiento (permisos y terceros)
                $validacionDetalles = $this->cuentaService->validarDetallesAsiento($request->detalles);
                if (!$validacionDetalles['valido']) {
                    return [
                        'success' => false,
                        'message' => $validacionDetalles['mensaje']
                    ];
                }
            }

            // Calcular el próximo consecutivo basado en el consecutivo_actual del tipo
            $consecutivo = (int)($tipoComprobante->consecutivo_actual ?? 0) + 1;
            $numeroComprobante = (string)($tipoComprobante->prefijo ?? '') . $consecutivo;

            // Calcular totales de débito y crédito
            $totalDebito = 0;
            $totalCredito = 0;
            if ($request->has('detalles')) {
                foreach ($request->detalles as $detalle) {
                    $totalDebito += floatval($detalle['debito'] ?? 0);
                    $totalCredito += floatval($detalle['credito'] ?? 0);
                }
            }

            // Crear el asiento contable
            $asiento = AsientoContable::on('tenant')->create([
                'fecha_asiento' => $request->fecha_asiento,
                'tipo_comprobante_id' => $request->tipo_comprobante_id,
                // numero_comprobante debe incluir el prefijo del tipo (ej. CV1, AJ2, ...)
                'numero_comprobante' => $numeroComprobante,
                // mantener campo numerico sin prefijo en 'consecutivo'
                'consecutivo' => $consecutivo,
                'concepto' => $request->concepto,
                'total_debito' => $totalDebito,
                'total_credito' => $totalCredito,
                'estado' => 'BORRADOR',
                'usuario_creacion' => Auth::id(),
                'fecha_creacion' => now(),
            ]);

            // Actualizar el consecutivo_actual del tipo al último usado (sin prefijo)
            $tipoComprobante->consecutivo_actual = $consecutivo;
            $tipoComprobante->save();

            // Crear los detalles
            if ($request->has('detalles')) {
                // Validar que todos los detalles tengan concepto
                foreach ($request->detalles as $index => $detalle) {
                    if (empty($detalle['concepto']) || trim($detalle['concepto']) === '') {
                        return [
                            'success' => false,
                            'message' => "El detalle " . ($index + 1) . " debe tener un concepto válido"
                        ];
                    }
                }

                $orden = 1;
                foreach ($request->detalles as $detalle) {
                    DetalleAsientoContable::on('tenant')->create([
                        'asiento_contable_id' => $asiento->id,
                        'cuenta_contable_id' => $detalle['cuenta_contable_id'],
                        'person_id' => !empty($detalle['tercero_id']) ? $detalle['tercero_id'] : null, // Mapear tercero_id a person_id
                        'concepto' => trim($detalle['concepto']),
                        'debito' => floatval($detalle['debito'] ?? 0),
                        'credito' => floatval($detalle['credito'] ?? 0),
                        'orden' => $orden++,
                    ]);
                }
            }

            // Procesar archivos adjuntos si los hay
            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $archivo) {
                    $this->procesarAdjunto($archivo, $asiento->id);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Asiento contable creado exitosamente',
                'data' => $asiento->load(['detalles.cuentaContable', 'tipoComprobante'])
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error al crear el asiento contable: ' . $e->getMessage()
            ];
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $this->ensureTenantConnection();

            DB::beginTransaction();

            $asiento = AsientoContable::on('tenant')->withTrashed()->findOrFail($id);

            // No permitir editar si fue eliminado (soft-deleted)
            if ($asiento->trashed()) {
                return [
                    'success' => false,
                    'message' => 'No se puede modificar un asiento eliminado'
                ];
            }

            // Solo permitir edición si está en borrador (case-insensitive)
            if (strtolower(trim($asiento->estado)) !== 'borrador') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden modificar asientos en estado borrador'
                ];
            }

            // Validar partida doble
            if ($request->has('detalles')) {
                $validacionPartidaDoble = $this->cuentaService->validarPartidaDoble($request->detalles);
                if (!$validacionPartidaDoble['valido']) {
                    return [
                        'success' => false,
                        'message' => $validacionPartidaDoble['mensaje']
                    ];
                }

                // Validar detalles del asiento (permisos y terceros)
                $validacionDetalles = $this->cuentaService->validarDetallesAsiento($request->detalles);
                if (!$validacionDetalles['valido']) {
                    return [
                        'success' => false,
                        'message' => $validacionDetalles['mensaje']
                    ];
                }
            }

            // Guardar detalles anteriores para revertir saldos
            $detallesAnteriores = $asiento->detalles->map(function($detalle) {
                return [
                    'cuenta_contable_id' => $detalle->cuenta_contable_id,
                    'debito' => $detalle->debito,
                    'credito' => $detalle->credito
                ];
            })->toArray();

            // Actualizar datos del asiento (sin los totales, se actualizarán después)
            $asiento->update([
                'fecha_asiento' => $request->fecha_asiento,
                'concepto' => $request->concepto,
                'fecha_modificacion' => now(),
            ]);

            // Eliminar detalles existentes y crear nuevos
            $asiento->detalles()->delete();

            if ($request->has('detalles')) {
                \Log::info('Detalles recibidos en update', $request->detalles);

                // Validar que todos los detalles tengan concepto
                foreach ($request->detalles as $index => $detalle) {
                    if (empty($detalle['concepto']) || trim($detalle['concepto']) === '') {
                        return [
                            'success' => false,
                            'message' => "El detalle " . ($index + 1) . " debe tener un concepto válido"
                        ];
                    }
                }

                // Calcular totales antes de crear los detalles
                $totalDebito = 0;
                $totalCredito = 0;
                $orden = 1; // Inicializar contador de orden

                foreach ($request->detalles as $detalle) {
                    // Debug: mostrar los valores exactos recibidos
                    \Log::info('Detalle recibido RAW', [
                        'cuenta_contable_id' => $detalle['cuenta_contable_id'] ?? 'NULL',
                        'tercero_id' => $detalle['tercero_id'] ?? 'NULL',
                        'concepto' => $detalle['concepto'] ?? 'NULL',
                        'debe_raw' => $detalle['debe'] ?? 'NULL',
                        'haber_raw' => $detalle['haber'] ?? 'NULL',
                        'debito_raw' => $detalle['debito'] ?? 'NULL',
                        'credito_raw' => $detalle['credito'] ?? 'NULL',
                        'orden' => $orden
                    ]);

                    // Limpiar y convertir valores numéricos de forma más robusta
                    $debitoValue = 0;
                    $creditoValue = 0;

                    // Priorizar debe/haber, luego debito/credito
                    if (isset($detalle['debe']) && $detalle['debe'] !== '' && $detalle['debe'] !== null) {
                        $debitoValue = floatval(str_replace(',', '', $detalle['debe']));
                    } elseif (isset($detalle['debito']) && $detalle['debito'] !== '' && $detalle['debito'] !== null) {
                        $debitoValue = floatval(str_replace(',', '', $detalle['debito']));
                    }

                    if (isset($detalle['haber']) && $detalle['haber'] !== '' && $detalle['haber'] !== null) {
                        $creditoValue = floatval(str_replace(',', '', $detalle['haber']));
                    } elseif (isset($detalle['credito']) && $detalle['credito'] !== '' && $detalle['credito'] !== null) {
                        $creditoValue = floatval(str_replace(',', '', $detalle['credito']));
                    }

                    // Acumular totales
                    $totalDebito += $debitoValue;
                    $totalCredito += $creditoValue;

                    $detalleCreado = [
                        'asiento_contable_id' => $asiento->id,
                        'cuenta_contable_id' => intval($detalle['cuenta_contable_id']),
                        'person_id' => !empty($detalle['tercero_id']) ? intval($detalle['tercero_id']) : null,
                        'concepto' => trim($detalle['concepto']),
                        'debito' => $debitoValue,
                        'credito' => $creditoValue,
                        'orden' => $orden, // Usar el contador consecutivo
                    ];

                    \Log::info('Detalle a crear (procesado)', $detalleCreado);

                    try {
                        DetalleAsientoContable::on('tenant')->create($detalleCreado);
                        \Log::info('Detalle creado exitosamente');
                        $orden++; // Incrementar orden para el siguiente detalle
                    } catch (Exception $e) {
                        \Log::error('Error creando detalle', [
                            'error' => $e->getMessage(),
                            'data' => $detalleCreado
                        ]);
                        throw $e;
                    }
                }

                // Actualizar los totales en el asiento principal
                \Log::info('Actualizando totales del asiento', [
                    'total_debito' => $totalDebito,
                    'total_credito' => $totalCredito
                ]);

                $asiento->update([
                    'total_debito' => $totalDebito,
                    'total_credito' => $totalCredito,
                ]);
            }

            // Procesar archivos adjuntos si los hay
            if ($request->hasFile('adjuntos')) {
                \Log::info('Procesando adjuntos en update', ['count' => count($request->file('adjuntos'))]);
                foreach ($request->file('adjuntos') as $archivo) {
                    \Log::info('Procesando adjunto', ['filename' => $archivo->getClientOriginalName()]);
                    $this->procesarAdjunto($archivo, $asiento->id);
                }
            } else {
                \Log::info('No se recibieron adjuntos en update');
            }

            // NOTA: Los asientos en BORRADOR no afectan saldos.
            // Solo cuando se CONFIRMAN se actualizan los saldos.
            // Por lo tanto, NO actualizamos saldos aquí en update().

            DB::commit();

            return [
                'success' => true,
                'message' => 'Asiento contable actualizado exitosamente',
                'data' => $asiento->load(['detalles.cuentaContable', 'detalles.tercero', 'tipoComprobante', 'adjuntos'])
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error al actualizar el asiento contable: ' . $e->getMessage()
            ];
        }
    }

    public function destroy($id)
    {
        try {
            $this->ensureTenantConnection();

            $asiento = AsientoContable::on('tenant')->findOrFail($id);

            // Solo permitir eliminación si está en borrador
            if (strtoupper($asiento->estado) !== 'BORRADOR') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden eliminar asientos en estado borrador'
                ];
            }

            // Los asientos en BORRADOR no afectan saldos, por lo tanto
            // no es necesario revertir saldos al eliminarlos

            $asiento->delete();

            return [
                'success' => true,
                'message' => 'Asiento contable eliminado exitosamente'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar el asiento contable: ' . $e->getMessage()
            ];
        }
    }

    // Confirmar (aprobar) un asiento contable
    public function confirmar($id)
    {
        try {
            $this->ensureTenantConnection();

            DB::beginTransaction();

            $asiento = AsientoContable::on('tenant')->findOrFail($id);

            if (strtoupper($asiento->estado) !== 'BORRADOR') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden aprobar asientos en estado borrador'
                ];
            }

            // Validar que esté balanceado antes de confirmar
            $asiento->load('detalles.cuentaContable');
            $asiento->calcularTotales();
            if (!$asiento->estaBalanceado()) {
                return [
                    'success' => false,
                    'message' => 'El asiento no está balanceado. La diferencia entre Débito y Crédito debe ser 0 para aprobar.'
                ];
            }

            // Actualizar saldos de cuentas contables
            $this->cuentaService->actualizarSaldosPorAsiento($asiento->id, 'crear');

            // Si es comprobante de Saldos Iniciales (código 24), actualizar saldo_inicial en cuentas_contables
            $tipoComprobante = TipoComprobanteContable::on('tenant')->find($asiento->tipo_comprobante_id);
            if ($tipoComprobante && $tipoComprobante->codigo === '24') {
                // Actualizar el saldo inicial de cada cuenta según los detalles del asiento
                foreach ($asiento->detalles as $detalle) {
                    $cuenta = CuentaContable::on('tenant')->find($detalle->cuenta_contable_id);
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

            // Confirmar usando la lógica del modelo
            $asiento->confirmar(Auth::id());

            DB::commit();

            return [
                'success' => true,
                'message' => 'Asiento contable aprobado exitosamente'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error al aprobar el asiento contable: ' . $e->getMessage()
            ];
        }
    }

    // Anular asiento (cambiar estado), opcional: mantener para futuros flujos
    public function anular($id)
    {
        try {
            $this->ensureTenantConnection();

            DB::beginTransaction();

            $asiento = AsientoContable::on('tenant')->findOrFail($id);

            if (strtoupper($asiento->estado) !== 'CONFIRMADO') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden anular asientos confirmados'
                ];
            }

            // Revertir saldos de las cuentas afectadas
            $this->cuentaService->revertirSaldosPorAsiento($asiento->id);

            $asiento->update([
                'estado' => 'ANULADO',
                'fecha_anulacion' => now(),
                'usuario_anulacion' => Auth::id(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Asiento contable anulado exitosamente'
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error al anular el asiento contable: ' . $e->getMessage()
            ];
        }
    }

    public function getTiposComprobantes()
    {
        try {
            // Verificar que la conexión tenant esté configurada
            $this->ensureTenantConnection();

            $tipos = TipoComprobanteContable::on('tenant')->activos()
                ->orderBy('codigo')
                ->get();

            return [
                'success' => true,
                'data' => $tipos
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al cargar tipos de comprobantes: ' . $e->getMessage()
            ];
        }
    }

    public function verificarSaldosIniciales()
    {
        try {
            $this->ensureTenantConnection();

            // Buscar el tipo de comprobante de Saldos Iniciales (código 24)
            $tipoSaldosIniciales = TipoComprobanteContable::on('tenant')
                ->where('codigo', '24')
                ->first();

            if (!$tipoSaldosIniciales) {
                return [
                    'success' => true,
                    'data' => ['existe' => false]
                ];
            }

            // Verificar si existe un asiento de Saldos Iniciales (en cualquier estado)
            $existeSaldosIniciales = AsientoContable::on('tenant')
                ->where('tipo_comprobante_id', $tipoSaldosIniciales->id)
                ->whereIn('estado', ['BORRADOR', 'CONFIRMADO'])
                ->exists();

            return [
                'success' => true,
                'data' => ['existe' => $existeSaldosIniciales]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar saldos iniciales: ' . $e->getMessage()
            ];
        }
    }

    public function getCuentasContables()
    {
        try {
            $this->ensureTenantConnection();

            $cuentas = CuentaContable::on('tenant')->where('activa', true)
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'descripcion', 'naturaleza', 'requiere_tercero']);

            return [
                'success' => true,
                'data' => $cuentas
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al cargar cuentas contables: ' . $e->getMessage()
            ];
        }
    }

    public function getTerceros()
    {
        try {
            $this->ensureTenantConnection();

            $terceros = Person::on('tenant')
                ->where('enabled', true)
                ->orderBy('name')
                ->get(['id', 'name', 'number']);

            return [
                'success' => true,
                'data' => $terceros
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al cargar terceros: ' . $e->getMessage()
            ];
        }
    }

    private function generarNumeroComprobante($tipoComprobante)
    {
        // Implementación thread-safe para evitar duplicados en acceso concurrente
        $maxIntentos = 5;
        $intento = 0;

        do {
            try {
                // Usar lockForUpdate() para bloquear la fila durante la transacción
                $ultimoAsiento = AsientoContable::on('tenant')
                    ->where('tipo_comprobante_id', $tipoComprobante->id)
                    ->orderBy('numero_comprobante', 'desc')
                    ->lockForUpdate()
                    ->first();

                $nuevoNumero = $ultimoAsiento ? $ultimoAsiento->numero_comprobante + 1 : 1;

                // Verificar que no existe ya este número (doble verificación)
                $existe = AsientoContable::on('tenant')
                    ->where('tipo_comprobante_id', $tipoComprobante->id)
                    ->where('numero_comprobante', $nuevoNumero)
                    ->exists();

                if (!$existe) {
                    return $nuevoNumero;
                }

                // Si existe, incrementar y reintentar
                $nuevoNumero++;
                $intento++;

            } catch (\Exception $e) {
                $intento++;
                if ($intento >= $maxIntentos) {
                    throw new \Exception('No se pudo generar un número de comprobante único después de ' . $maxIntentos . ' intentos');
                }
                // Esperar un momento antes de reintentar
                usleep(rand(10000, 50000)); // 10-50ms
            }
        } while ($intento < $maxIntentos);

        throw new \Exception('Error al generar número de comprobante');
    }

    private function procesarAdjunto($archivo, $asientoId)
    {
        if ($archivo->isValid()) {
            $nombreOriginal = $archivo->getClientOriginalName();
            $extension = $archivo->getClientOriginalExtension();
            $nombreArchivo = time() . '_' . $nombreOriginal;

            // Guardar en storage/app/asientos_adjuntos
            $ruta = $archivo->storeAs('asientos_adjuntos', $nombreArchivo);

            AsientoAdjunto::on('tenant')->create([
                'asiento_contable_id' => $asientoId,
                'nombre_archivo' => $nombreOriginal,
                'ruta_archivo' => $ruta,
                'tipo_archivo' => $extension,
                'tamaño_archivo' => $archivo->getSize(),
                'fecha_carga' => now(),
            ]);
        }
    }

    /**
     * Obtener el próximo número consecutivo para un tipo de comprobante
     */
    public function getProximoConsecutivo(Request $request)
    {
        try {
            $this->ensureTenantConnection();

            $tipoComprobanteId = $request->get('tipo_comprobante_id');
            if (!$tipoComprobanteId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de comprobante requerido'
                ], 400);
            }

            $tipoComprobante = TipoComprobanteContable::on('tenant')->find($tipoComprobanteId);
            if (!$tipoComprobante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de comprobante no encontrado'
                ], 404);
            }

            // El próximo consecutivo se basa en consecutivo_actual del tipo (sin reservar)
            $proximoNumero = (int)($tipoComprobante->consecutivo_actual ?? 0) + 1;
            $formateado = (string)($tipoComprobante->prefijo ?? '') . $proximoNumero;

            return response()->json([
                'success' => true,
                'data' => [
                    // consecutivo numérico sin prefijo
                    'proximo_consecutivo' => $proximoNumero,
                    // número completo con prefijo para mostrar en UI
                    'numero_formateado' => $formateado,
                    'tipo_comprobante' => $tipoComprobante->nombre ?? $tipoComprobante->descripcion ?? ''
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ensure tenant connection is properly configured
     */
    private function ensureTenantConnection()
    {
        $hostname = app(CurrentHostname::class);

        if (!$hostname) {
            throw new Exception('No se pudo determinar el hostname del tenant');
        }

        $website = $hostname->website;

        if (!$website) {
            throw new Exception('No se pudo determinar el website del tenant');
        }

        // Forzar la configuración de la conexión tenant
        $connection = app(Connection::class);
        $connection->set($website);

        // Verificar que podemos acceder a la base de datos
        try {
            DB::connection('tenant')->getPdo();
        } catch (Exception $e) {
            throw new Exception('No se pudo establecer conexión con la base de datos del tenant: ' . $e->getMessage());
        }
    }

    /**
     * Obtener el próximo consecutivo para un tipo de comprobante
     */
    private function obtenerProximoConsecutivo($tipoComprobanteId)
    {
        // Obtener el último consecutivo para este tipo de comprobante
        $ultimoConsecutivo = AsientoContable::on('tenant')
            ->where('tipo_comprobante_id', $tipoComprobanteId)
            ->max('consecutivo');

        return ($ultimoConsecutivo ?? 0) + 1;
    }

    /**
     * Descargar un adjunto del asiento contable
     */
    public function descargarAdjunto($id)
    {
        $this->ensureTenantConnection();

        try {
            $adjunto = AsientoAdjunto::on('tenant')->findOrFail($id);

            // Verificar que el archivo existe
            if (!Storage::disk('tenant')->exists($adjunto->ruta_archivo)) {
                abort(404, 'Archivo no encontrado');
            }

            // Obtener el contenido del archivo
            $contenido = Storage::disk('tenant')->get($adjunto->ruta_archivo);

            // Determinar el tipo MIME
            $mimeType = Storage::disk('tenant')->mimeType($adjunto->ruta_archivo);

            // Retornar la respuesta de descarga
            return response($contenido)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'attachment; filename="' . $adjunto->nombre_archivo . '"')
                ->header('Content-Length', strlen($contenido));

        } catch (Exception $e) {
            \Log::error('Error al descargar adjunto: ' . $e->getMessage());
            abort(404, 'Error al descargar el archivo');
        }
    }

    /**
     * Imprimir reporte PDF del asiento contable
     */
    public function imprimir($id)
    {
        $this->ensureTenantConnection();

        $asiento = AsientoContable::on('tenant')
            ->withTrashed()
            ->with(['detalles.cuentaContable', 'detalles.tercero', 'tipoComprobante'])
            ->findOrFail($id);

        $company = \App\Models\Tenant\Company::active();

        $pdf = \PDF::loadView('tenant.asientos_contables.reporte_pdf', [
            'company' => $company,
            'asiento' => $asiento,
        ])->setPaper('A4', 'portrait');

        $filename = 'Asiento_' . ($asiento->numero_comprobante ?? ('ID' . $asiento->id)) . '.pdf';
        return $pdf->stream($filename);
    }
}
