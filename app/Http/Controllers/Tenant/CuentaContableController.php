<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Tenant\CuentaContable;
use App\Http\Controllers\Controller;
use App\Http\Resources\Tenant\CuentaContableCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class CuentaContableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('tenant.cuentas_contables.index');
    }

    /**
     * Download PUC template file
     */
    public function downloadTemplate(Request $request)
    {
        $format = $request->get('format', 'csv'); // csv o excel

        if ($format === 'excel') {
            // Generar archivo Excel basado en el archivo original PUC_inicial.xlsx
            $filename = 'Plan_de_Cuentas_Inicial_' . date('Y-m-d') . '.xlsx';

            // Usar export que lee el archivo original
            $export = new \App\Exports\PucInicialExport;

            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } else {
            // Generar archivo CSV basado en el archivo original PUC_inicial.xlsx
            $filename = 'Plan_de_Cuentas_Inicial_' . date('Y-m-d') . '.csv';

            $export = new \App\Exports\PucInicialExport;
            $csvData = $export->getDataForCSV();

            return response()->streamDownload(function() use ($csvData) {
                $file = fopen('php://output', 'w');

                // Escribir BOM para UTF-8
                fwrite($file, "\xEF\xBB\xBF");

                // Escribir todos los datos (headers + datos)
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }

                fclose($file);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }
    }

    /**
     * Get table columns for DataTable component
     */
    public function columns()
    {
        return response()->json([
            'codigo' => 'Código',
            'nombre' => 'Nombre',
            'tipo_cuenta' => 'Tipo',
            'naturaleza' => 'Naturaleza',
            'nivel' => 'Nivel',
            'activa' => 'Estado'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tenant.cuentas_contables.form');
    }

    /**
     * Get all records for API/AJAX requests
     */
    public function records(Request $request)
    {
        try {
            // Verificar que la conexión tenant esté configurada
            $this->ensureTenantConnection();

            $query = CuentaContable::on('tenant');

            // Búsqueda por columna específica (patrón DataTable)
            if ($request->has('column') && $request->has('value') && $request->value != '') {
                $column = $request->column;
                $value = $request->value;

                if (in_array($column, ['codigo', 'nombre', 'tipo_cuenta', 'naturaleza', 'nivel'])) {
                    if ($column === 'activa') {
                        $query->where($column, $value == '1');
                    } else {
                        $query->where($column, 'like', "%{$value}%");
                    }
                }
            }

        // Filtros adicionales
        if ($request->has('tipo_cuenta') && $request->tipo_cuenta != '') {
            $query->where('tipo_cuenta', $request->tipo_cuenta);
        }

        if ($request->has('naturaleza') && $request->naturaleza != '') {
            $query->where('naturaleza', $request->naturaleza);
        }

        if ($request->has('nivel') && $request->nivel != '') {
            $query->where('nivel', $request->nivel);
        }

        if ($request->has('activa') && $request->activa != '') {
            $query->where('activa', $request->activa == '1');
        }

        if ($request->has('permite_movimiento') && $request->permite_movimiento != '') {
            $query->where('permite_movimiento', $request->permite_movimiento == '1');
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;

            // Buscar cuentas que coincidan con el criterio
            $matchingAccountIds = CuentaContable::on('tenant')
                ->where(function($q) use ($search) {
                    $q->where('codigo', 'like', "%{$search}%")
                      ->orWhere('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                })
                ->pluck('id')
                ->toArray();

            if (!empty($matchingAccountIds)) {
                // Para cada cuenta encontrada, incluir toda su jerarquía (padres y hijos)
                $allRelatedIds = [];

                foreach ($matchingAccountIds as $accountId) {
                    $account = CuentaContable::on('tenant')->find($accountId);
                    if ($account) {
                        // Agregar la cuenta actual
                        $allRelatedIds[] = $accountId;

                        // Agregar todos los ancestros (padres, abuelos, etc.)
                        $current = $account;
                        while ($current && $current->cuenta_padre_id) {
                            $allRelatedIds[] = $current->cuenta_padre_id;
                            $current = $current->cuentaPadre;
                        }

                        // Agregar todos los descendientes
                        $descendants = $this->getAllDescendants($accountId);
                        $allRelatedIds = array_merge($allRelatedIds, $descendants);
                    }
                }

                $allRelatedIds = array_unique($allRelatedIds);
                $query->whereIn('id', $allRelatedIds);
            } else {
                // Si no se encuentran coincidencias, no mostrar nada
                $query->where('id', -1);
            }
        }

        // Incluir relación con cuenta padre
        $query->with('cuentaPadre');

        // Ordenamiento
        $orderBy = $request->get('order_by', 'codigo');
        $orderDirection = $request->get('order_direction', 'asc');
        $query->orderBy($orderBy, $orderDirection);

        // Paginación
        $perPage = $request->get('per_page', config('tenant.items_per_page', 20));
        $results = $query->paginate($perPage);

        // Usar ResourceCollection para formato compatible con DataTable
        return new CuentaContableCollection($results);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los registros',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search accounts for select dropdowns (autocomplete)
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $limit = $request->get('limit', 20);

            $results = CuentaContable::on('tenant')
                ->where(function($q) use ($query) {
                    $q->where('codigo', 'like', "%{$query}%")
                      ->orWhere('nombre', 'like', "%{$query}%");
                })
                ->where('activa', true)
                ->orderBy('codigo', 'asc')
                ->limit($limit)
                ->get(['codigo', 'nombre', 'tipo_cuenta', 'naturaleza']);

            return response()->json([
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al buscar cuentas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get records in tree structure
     */
    public function tree(Request $request)
    {
        // Si hay búsqueda, cambiar estrategia para mostrar jerarquía completa
        if ($request->has('search') && $request->search != '') {
            return $this->treeWithSearch($request);
        }

        $query = CuentaContable::raiz();

        // Aplicar filtros si están presentes
        if ($request->has('tipo_cuenta') && $request->tipo_cuenta != '') {
            $query->where('tipo_cuenta', $request->tipo_cuenta);
        }

        if ($request->has('naturaleza') && $request->naturaleza != '') {
            $query->where('naturaleza', $request->naturaleza);
        }

        if ($request->has('activa') && $request->activa != '') {
            $query->where('activa', $request->activa == '1');
        } else {
            $query->activas(); // Por defecto solo mostrar activas
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        $cuentasRaiz = $query->with(['descendientes' => function($queryDesc) use ($request) {
                // Aplicar los mismos filtros a los descendientes
                if ($request->has('activa') && $request->activa != '') {
                    $queryDesc->where('activa', $request->activa == '1');
                } else {
                    $queryDesc->where('activa', true);
                }

                if ($request->has('tipo_cuenta') && $request->tipo_cuenta != '') {
                    $queryDesc->where('tipo_cuenta', $request->tipo_cuenta);
                }

                if ($request->has('naturaleza') && $request->naturaleza != '') {
                    $queryDesc->where('naturaleza', $request->naturaleza);
                }

                if ($request->has('search') && $request->search != '') {
                    $search = $request->search;
                    $queryDesc->where(function($q) use ($search) {
                        $q->where('codigo', 'like', "%{$search}%")
                          ->orWhere('nombre', 'like', "%{$search}%")
                          ->orWhere('descripcion', 'like', "%{$search}%");
                    });
                }
            }])
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $this->buildTree($cuentasRaiz, $request->all())
        ]);
    }

    /**
     * Build hierarchical tree structure
     */
    private function buildTree($cuentas, $filters = [])
    {
        return $cuentas->map(function($cuenta) use ($filters) {
            // Obtener hijas con filtros aplicados
            $hijasQuery = $cuenta->cuentasHijas()->orderBy('codigo');

            // Aplicar filtros a las cuentas hijas
            if (isset($filters['activa']) && $filters['activa'] !== '') {
                $hijasQuery->where('activa', $filters['activa'] == '1');
            } else {
                $hijasQuery->activas();
            }

            if (isset($filters['tipo_cuenta']) && $filters['tipo_cuenta'] != '') {
                $hijasQuery->where('tipo_cuenta', $filters['tipo_cuenta']);
            }

            if (isset($filters['naturaleza']) && $filters['naturaleza'] != '') {
                $hijasQuery->where('naturaleza', $filters['naturaleza']);
            }

            if (isset($filters['search']) && $filters['search'] != '') {
                $search = $filters['search'];
                $hijasQuery->where(function($q) use ($search) {
                    $q->where('codigo', 'like', "%{$search}%")
                      ->orWhere('nombre', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }

            $hijas = $hijasQuery->get();

            return [
                'id' => $cuenta->id,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'tipo_cuenta' => $cuenta->tipo_cuenta,
                'naturaleza' => $cuenta->naturaleza,
                'nivel' => $cuenta->nivel,
                'activa' => $cuenta->activa,
                'permite_movimiento' => $cuenta->permite_movimiento,
                'saldo_actual' => $cuenta->saldo_actual,
                'es_cuenta_movimiento' => $cuenta->esCuentaMovimiento(),
                'children' => $this->buildTree($hijas, $filters)
            ];
        });
    }

    /**
     * Get accounts for movement (leaf accounts)
     */
    public function movimiento()
    {
        $cuentas = CuentaContable::activas()
            ->movimiento()
            ->whereDoesntHave('cuentasHijas')
            ->orderBy('codigo')
            ->get()
            ->map(function($cuenta) {
                return [
                    'id' => $cuenta->id,
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'codigo_nombre' => $cuenta->codigo . ' - ' . $cuenta->nombre,
                    'tipo_cuenta' => $cuenta->tipo_cuenta,
                    'naturaleza' => $cuenta->naturaleza,
                    'saldo_actual' => $cuenta->saldo_actual
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $cuentas
        ]);
    }

    /**
     * Get parent accounts options
     */
    public function padres(Request $request)
    {
        $nivel = $request->get('nivel', 1);
        $tipoRequest = $request->get('tipo_cuenta');

        $query = CuentaContable::activas();

        if ($nivel > 1) {
            $query->where('nivel', $nivel - 1);
        } else {
            $query->raiz();
        }

        if ($tipoRequest) {
            $query->where('tipo_cuenta', $tipoRequest);
        }

        $cuentas = $query->orderBy('codigo')
            ->get()
            ->map(function($cuenta) {
                return [
                    'id' => $cuenta->id,
                    'codigo' => $cuenta->codigo,
                    'nombre' => $cuenta->nombre,
                    'codigo_nombre' => $cuenta->codigo . ' - ' . $cuenta->nombre,
                    'nivel' => $cuenta->nivel,
                    'tipo_cuenta' => $cuenta->tipo_cuenta
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $cuentas
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Verificar que la conexión tenant esté configurada
            $this->ensureTenantConnection();

            DB::connection('tenant')->beginTransaction();

            // Validaciones básicas sin la validación de unicidad
            $rules = CuentaContable::rules();
            // Remover la regla de unicidad para manejarla manualmente
            unset($rules['codigo']);

            $request->validate($rules);

            // Validar unicidad del código manualmente
            $codigoExistente = CuentaContable::on('tenant')->where('codigo', $request->codigo)->exists();
            if ($codigoExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => ['codigo' => ['El código ya existe']]
                ], 422);
            }

            // Validar código requerido
            if (!$request->codigo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => ['codigo' => ['El código es requerido']]
                ], 422);
            }

            // Validaciones adicionales de negocio
            $this->validarReglasNegocio($request);

            // Crear la cuenta usando la conexión tenant explícitamente
            $cuenta = new CuentaContable();
            $cuenta->setConnection('tenant');
            $cuenta->fill($request->all());

            // Auto-asignar naturaleza si no se especifica
            if (!$cuenta->naturaleza && $cuenta->tipo_cuenta) {
                $cuenta->naturaleza = CuentaContable::getNaturalezaPorTipo($cuenta->tipo_cuenta);
            }

            $cuenta->save();

            DB::connection('tenant')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta contable creada exitosamente',
                'data' => $cuenta
            ]);

        } catch (ValidationException $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cuenta contable: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $cuenta = CuentaContable::with(['cuentaPadre', 'cuentasHijas'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'cuenta' => $cuenta,
                    'path_completo' => $cuenta->getPathCompleto(),
                    'ascendientes' => $cuenta->ascendientes(),
                    'es_cuenta_movimiento' => $cuenta->esCuentaMovimiento()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta contable no encontrada'
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $cuenta = CuentaContable::findOrFail($id);
        return view('tenant.cuentas_contables.form', compact('cuenta'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Verificar que la conexión tenant esté configurada
            $this->ensureTenantConnection();

            DB::beginTransaction();

            $cuenta = CuentaContable::findOrFail($id);

            // Validaciones básicas
            $rules = CuentaContable::rules($id);
            $request->validate($rules);

            // Validaciones adicionales de negocio
            $this->validarReglasNegocio($request, $cuenta);

            $cuenta->fill($request->all());
            $cuenta->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta contable actualizada exitosamente',
                'data' => $cuenta
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cuenta contable: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Verificar que la conexión tenant esté configurada
            $this->ensureTenantConnection();

            DB::beginTransaction();

            $cuenta = CuentaContable::findOrFail($id);

            // Verificar si tiene cuentas hijas
            if ($cuenta->cuentasHijas()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una cuenta que tiene cuentas hijas'
                ], 422);
            }

            // Verificar si permite eliminación (se puede agregar más lógica aquí)
            // Como verificar si tiene movimientos contables

            $cuenta->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta contable eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cuenta contable: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tables data for form selects
     */
    public function tables()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'tipos_cuenta' => CuentaContable::TIPOS_CUENTA,
                'naturalezas' => CuentaContable::NATURALEZAS,
                'naturaleza_por_tipo' => CuentaContable::NATURALEZA_POR_TIPO
            ]
        ]);
    }

    /**
     * Validate business rules
     */
    private function validarReglasNegocio(Request $request, CuentaContable $cuenta = null)
    {
        $errores = [];

        // Validar naturaleza según tipo de cuenta
        if ($request->naturaleza && $request->tipo_cuenta) {
            $naturalezaEsperada = CuentaContable::getNaturalezaPorTipo($request->tipo_cuenta);
            if ($request->naturaleza !== $naturalezaEsperada) {
                $errores['naturaleza'] = ["La naturaleza debe ser '{$naturalezaEsperada}' para el tipo de cuenta '{$request->tipo_cuenta}'"];
            }
        }

        // Validar jerarquía con cuenta padre
        if ($request->cuenta_padre_id) {
            $cuentaPadre = CuentaContable::on('tenant')->find($request->cuenta_padre_id);
            if (!$cuentaPadre) {
                $errores['cuenta_padre_id'] = ["La cuenta padre especificada no existe"];
            } else {
                // Validar nivel
                if ($request->nivel != ($cuentaPadre->nivel + 1)) {
                    $errores['nivel'] = ["El nivel debe ser " . ($cuentaPadre->nivel + 1) . " para mantener la jerarquía"];
                }

                // Validar tipo de cuenta
                if ($request->tipo_cuenta !== $cuentaPadre->tipo_cuenta) {
                    $errores['tipo_cuenta'] = ["El tipo de cuenta debe ser igual al de la cuenta padre ({$cuentaPadre->tipo_cuenta})"];
                }

                // Evitar auto-referencia
                if ($cuenta && $request->cuenta_padre_id == $cuenta->id) {
                    $errores['cuenta_padre_id'] = ["Una cuenta no puede ser padre de sí misma"];
                }
            }
        } else {
            // Cuenta raíz debe ser nivel 1
            if ($request->nivel != 1) {
                $errores['nivel'] = ["Las cuentas raíz deben tener nivel 1"];
            }
        }

        if (!empty($errores)) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * Import accounts from PUC (Plan Único de Cuentas)
     */
    public function importPuc(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:csv,txt,xlsx,xls',
                'sobreescribir' => 'sometimes|in:0,1,true,false'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $file = $request->file('archivo');
            $sobreescribir = in_array($request->get('sobreescribir'), ['1', 'true', true], true);

            // Determinar tipo de archivo
            $extension = strtolower($file->getClientOriginalExtension());

            $cuentasImportadas = 0;
            $cuentasActualizadas = 0;
            $errores = [];

            // Verificar que la conexión tenant esté configurada
            $this->ensureTenantConnection();

            if (in_array($extension, ['csv', 'txt'])) {
                // Procesar archivo CSV en dos fases para manejar jerarquía
                $handle = fopen($file->getPathname(), 'r');

                if ($handle === false) {
                    throw new \Exception('No se pudo abrir el archivo');
                }

                // Leer primera fila (encabezados)
                $headers = fgetcsv($handle);
                if (!$headers) {
                    throw new \Exception('El archivo está vacío o no tiene el formato correcto');
                }

                // Validar encabezados - esperamos: codigo, nombre, tipo_cuenta, naturaleza, nivel, cuenta_padre_codigo, descripcion, activa, permite_movimiento, requiere_tercero
                $expectedHeaders = ['codigo', 'nombre', 'tipo_cuenta', 'naturaleza', 'nivel', 'cuenta_padre_codigo', 'descripcion', 'activa', 'permite_movimiento', 'requiere_tercero'];
                $normalizedHeaders = array_map('trim', array_map('strtolower', $headers));
                $normalizedExpected = array_map('strtolower', $expectedHeaders);

                if ($normalizedHeaders !== $normalizedExpected) {
                    fclose($handle);
                    throw new \Exception('El formato de encabezados del CSV no es válido. Se esperan: ' . implode(', ', $expectedHeaders));
                }

                // Leer todos los datos primero
                $cuentasData = [];
                $rowNumber = 1;
                while (($data = fgetcsv($handle)) !== false) {
                    $rowNumber++;

                    if (count($data) < count($expectedHeaders)) {
                        $errores[] = "Fila $rowNumber: Faltan columnas";
                        continue;
                    }

                    // Saltar filas vacías
                    if (empty(array_filter($data))) {
                        continue;
                    }

                    // Procesar y normalizar tipos de cuenta
                    $tipoRaw = trim($data[2]);
                    $tipo_cuenta = $this->normalizarTexto($tipoRaw); // Usar normalización de caracteres

                    // Mapear tipos de cuenta completos (usando texto normalizado)
                    $mapeoTipos = [
                        // Variaciones plurales
                        'activos' => 'activo',
                        'pasivos' => 'pasivo',
                        'ingresos' => 'ingreso',
                        'gastos' => 'gasto',
                        'costos' => 'costo',
                        'egresos' => 'gasto',

                        // Tipos específicos de costos
                        'costos de venta' => 'costo',
                        'costos de ventas' => 'costo',
                        'costo de venta' => 'costo',
                        'costo de ventas' => 'costo',
                        'costos de produccion' => 'costo',
                        'costos de produccion o de operacion' => 'costo',
                        'costos de operacion' => 'costo',

                        // Cuentas de orden
                        'cuentas de orden' => 'activo',
                        'cuentas de orden deudoras' => 'activo',
                        'cuentas de orden acreedoras' => 'pasivo',
                        'cuenta de orden' => 'activo',
                        'cuenta de orden deudora' => 'activo',
                        'cuenta de orden acreedora' => 'pasivo',

                        // Cadenas UTF-8 corruptas específicas
                        hex2bin('636f73746f732064652070726f6475636369e3b36e206f206465206f706572616369e3b36e') => 'costo',
                    ];

                    if (isset($mapeoTipos[$tipo_cuenta])) {
                        $tipo_cuenta = $mapeoTipos[$tipo_cuenta];
                    }

                    $cuentasData[] = [
                        'fila' => $rowNumber,
                        'codigo' => trim($data[0]),
                        'nombre' => trim($data[1]),
                        'tipo_cuenta' => $tipo_cuenta,
                        'naturaleza' => strtolower(trim($data[3])),
                        'nivel' => intval($data[4]),
                        'codigo_padre' => !empty(trim($data[5])) ? trim($data[5]) : null,
                        'descripcion' => trim($data[6] ?? ''),
                        'activa' => boolval($data[7] ?? 1),
                        'permite_movimiento' => boolval($data[8] ?? 1),
                        'requiere_tercero' => boolval($data[9] ?? 0)
                    ];
                }

                fclose($handle);

                // Procesar en dos fases
                $resultado = $this->procesarCuentasEnDosFases($cuentasData, $sobreescribir);
                $cuentasImportadas = $resultado['cuentasImportadas'];
                $cuentasActualizadas = $resultado['cuentasActualizadas'];
                $errores = array_merge($errores, $resultado['errores']);

            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                // Procesar archivo Excel usando Laravel Excel en dos fases
                try {
                    $data = Excel::toArray([], $file)[0]; // Obtener la primera hoja

                    if (empty($data)) {
                        throw new \Exception('El archivo Excel está vacío');
                    }

                    // Validar encabezados (primera fila) - sea flexible con el formato
                    $headers = $data[0] ?? [];
                    $normalizedHeaders = array_map('trim', array_map('strtolower', $headers));

                    // Headers esperados (flexible)
                    $requiredHeaders = ['codigo', 'nombre', 'tipo_cuenta', 'naturaleza', 'nivel'];
                    $missingHeaders = array_diff($requiredHeaders, $normalizedHeaders);

                    if (!empty($missingHeaders)) {
                        throw new \Exception('Faltan headers requeridos en el Excel: ' . implode(', ', $missingHeaders));
                    }

                    // Mapear posiciones de columnas
                    $columnMap = [];
                    foreach ($normalizedHeaders as $index => $header) {
                        $columnMap[$header] = $index;
                    }

                    // Eliminar la primera fila (encabezados)
                    array_shift($data);

                    // Preparar todos los datos
                    $cuentasData = [];
                    $rowNumber = 1;
                    foreach ($data as $row) {
                        $rowNumber++;

                        // Verificar que la fila no esté vacía
                        if (empty(array_filter($row))) {
                            continue;
                        }

                        // Extraer datos usando el mapeo de columnas
                        $codigo = trim((string)($row[$columnMap['codigo']] ?? ''));
                        $nombre = trim((string)($row[$columnMap['nombre']] ?? ''));
                        $tipoRaw = trim((string)($row[$columnMap['tipo_cuenta']] ?? ''));
                        $naturalezaRaw = trim((string)($row[$columnMap['naturaleza']] ?? ''));
                        $nivel = intval($row[$columnMap['nivel']] ?? 0);

                        // Normalizar tipo de cuenta y naturaleza
                        $tipo_cuenta = $this->normalizarTexto($tipoRaw); // Usar normalización de caracteres
                        $naturaleza = strtolower($naturalezaRaw);

                        // Mapear tipos de cuenta completos (usando texto normalizado)
                        $mapeoTipos = [
                            // Variaciones plurales
                            'activos' => 'activo',
                            'pasivos' => 'pasivo',
                            'ingresos' => 'ingreso',
                            'gastos' => 'gasto',
                            'costos' => 'costo',
                            'egresos' => 'gasto',

                            // Tipos específicos de costos
                            'costos de venta' => 'costo',
                            'costos de ventas' => 'costo',
                            'costo de venta' => 'costo',
                            'costo de ventas' => 'costo',
                            'costos de produccion' => 'costo',
                            'costos de produccion o de operacion' => 'costo',
                            'costos de operacion' => 'costo',

                            // Cuentas de orden
                            'cuentas de orden' => 'activo',
                            'cuentas de orden deudoras' => 'activo',
                            'cuentas de orden acreedoras' => 'pasivo',
                            'cuenta de orden' => 'activo',
                            'cuenta de orden deudora' => 'activo',
                            'cuenta de orden acreedora' => 'pasivo',

                            // Cadenas UTF-8 corruptas específicas
                            hex2bin('636f73746f732064652070726f6475636369e3b36e206f206465206f706572616369e3b36e') => 'costo',
                        ];

                        if (isset($mapeoTipos[$tipo_cuenta])) {
                            $tipo_cuenta = $mapeoTipos[$tipo_cuenta];
                        }

                        // Obtener cuenta padre
                        $codigoPadre = null;
                        if (isset($columnMap['cuenta_padre_codigo'])) {
                            $codigoPadre = !empty(trim((string)$row[$columnMap['cuenta_padre_codigo']])) ?
                                          trim((string)$row[$columnMap['cuenta_padre_codigo']]) : null;
                        } elseif (isset($columnMap['cuenta_padre_id'])) {
                            $codigoPadre = !empty(trim((string)$row[$columnMap['cuenta_padre_id']])) ?
                                          trim((string)$row[$columnMap['cuenta_padre_id']]) : null;
                        }

                        $descripcion = isset($columnMap['descripcion']) ?
                                      trim((string)($row[$columnMap['descripcion']] ?? '')) : '';
                        $activa = isset($columnMap['activa']) ?
                                 boolval($row[$columnMap['activa']] ?? 1) : true;
                        $permite_movimiento = isset($columnMap['permite_movimiento']) ?
                                             boolval($row[$columnMap['permite_movimiento']] ?? 1) : true;
                        $requiere_tercero = isset($columnMap['requiere_tercero']) ?
                                           boolval($row[$columnMap['requiere_tercero']] ?? 0) : false;

                        if (empty($codigo)) {
                            continue; // Saltar filas sin código
                        }

                        $cuentasData[] = [
                            'fila' => $rowNumber,
                            'codigo' => $codigo,
                            'nombre' => $nombre,
                            'tipo_cuenta' => $tipo_cuenta,
                            'naturaleza' => $naturaleza,
                            'nivel' => $nivel,
                            'codigo_padre' => $codigoPadre,
                            'descripcion' => $descripcion,
                            'activa' => $activa,
                            'permite_movimiento' => $permite_movimiento,
                            'requiere_tercero' => $requiere_tercero
                        ];
                    }

                    // Procesar en dos fases
                    $resultado = $this->procesarCuentasEnDosFases($cuentasData, $sobreescribir);
                    $cuentasImportadas = $resultado['cuentasImportadas'];
                    $cuentasActualizadas = $resultado['cuentasActualizadas'];
                    $errores = array_merge($errores, $resultado['errores']);

                } catch (\Exception $e) {
                    throw new \Exception('Error al procesar archivo Excel: ' . $e->getMessage());
                }
            } else {
                throw new \Exception('Formato de archivo no soportado');
            }

            DB::commit();

            $mensaje = "Importación completada: $cuentasImportadas cuentas importadas";
            if ($cuentasActualizadas > 0) {
                $mensaje .= ", $cuentasActualizadas cuentas actualizadas";
            }
            if (!empty($errores)) {
                $mensaje .= ". " . count($errores) . " errores encontrados";

                // Agregar algunos ejemplos de errores al mensaje
                if (count($errores) > 0) {
                    $ejemplosErrores = array_slice($errores, 0, 3);
                    $mensaje .= ". Ejemplos: " . implode('; ', $ejemplosErrores);
                    if (count($errores) > 3) {
                        $mensaje .= " (y " . (count($errores) - 3) . " más...)";
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'importadas' => $cuentasImportadas,
                    'actualizadas' => $cuentasActualizadas,
                    'errores' => $errores,
                    'resumen_errores' => array_slice($errores, 0, 20) // Primeros 20 errores para mostrar al usuario
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al importar PUC: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export accounts to various formats
     */
    public function export(Request $request)
    {
        $formato = $request->get('formato', 'excel');

        try {
            $cuentas = CuentaContable::with('cuentaPadre')
                ->orderBy('codigo')
                ->get();

            // Lógica de exportación según formato
            // Por ahora, retornar los datos

            return response()->json([
                'success' => true,
                'data' => $cuentas,
                'message' => 'Exportación preparada'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar archivo CSV
     */
    private function procesarArchivoCsv($file, &$errores)
    {
        $cuentas = [];
        $handle = fopen($file->getPathname(), 'r');

        if ($handle === false) {
            throw new \Exception('No se pudo abrir el archivo CSV');
        }

        // Leer encabezados
        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new \Exception('El archivo CSV está vacío o no tiene el formato correcto');
        }

        $rowNumber = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($data) < 9) {
                $errores[] = "Fila $rowNumber: Faltan columnas";
                continue;
            }

            $cuentaData = $this->validarDatosCuenta($data, $rowNumber, $errores);
            if ($cuentaData) {
                $cuentas[] = $cuentaData;
            }
        }

        fclose($handle);
        return $cuentas;
    }

    /**
     * Procesar archivo Excel
     */
    private function procesarArchivoExcel($file, &$errores)
    {
        $cuentas = [];

        try {
            $data = Excel::toArray([], $file)[0]; // Obtener la primera hoja

            if (empty($data)) {
                throw new \Exception('El archivo Excel está vacío');
            }

            // Eliminar la primera fila (encabezados)
            array_shift($data);

            $rowNumber = 1;
            foreach ($data as $row) {
                $rowNumber++;

                // Verificar que la fila no esté vacía
                if (empty(array_filter($row))) {
                    continue;
                }

                if (count($row) < 9) {
                    $errores[] = "Fila $rowNumber: Faltan columnas";
                    continue;
                }

                // Convertir todos los valores a string para consistencia
                $row = array_map(function($value) {
                    return (string)$value;
                }, $row);

                $cuentaData = $this->validarDatosCuenta($row, $rowNumber, $errores);
                if ($cuentaData) {
                    $cuentas[] = $cuentaData;
                }
            }

        } catch (\Exception $e) {
            throw new \Exception('Error al procesar archivo Excel: ' . $e->getMessage());
        }

        return $cuentas;
    }

    /**
     * Validar datos de una cuenta
     */
    private function validarDatosCuenta($data, $rowNumber, &$errores)
    {
        $cuentaData = [
            'codigo' => trim($data[0]),
            'nombre' => trim($data[1]),
            'tipo_cuenta' => trim($data[2]),
            'naturaleza' => trim($data[3]),
            'nivel' => intval($data[4]),
            'codigo_padre' => !empty(trim($data[5])) ? trim($data[5]) : null,
            'descripcion' => trim($data[6] ?? ''),
            'activa' => boolval($data[7] ?? 1),
            'permite_movimiento' => boolval($data[8] ?? 1),
            'fila' => $rowNumber
        ];

        // Validar datos básicos
        if (empty($cuentaData['codigo']) || empty($cuentaData['nombre'])) {
            $errores[] = "Fila $rowNumber: Código y nombre son obligatorios";
            return null;
        }

        // Validar tipo de cuenta
        if (!in_array($cuentaData['tipo_cuenta'], ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'costo'])) {
            $errores[] = "Fila $rowNumber: Tipo de cuenta inválido: {$cuentaData['tipo_cuenta']}";
            return null;
        }

        // Validar naturaleza
        if (!in_array($cuentaData['naturaleza'], ['debito', 'credito'])) {
            $errores[] = "Fila $rowNumber: Naturaleza inválida: {$cuentaData['naturaleza']}";
            return null;
        }

        return $cuentaData;
    }

    /**
     * Ensure tenant connection is properly configured
     */
    private function ensureTenantConnection()
    {
        $hostname = app(\Hyn\Tenancy\Contracts\CurrentHostname::class);

        if (!$hostname) {
            throw new \Exception('No se pudo determinar el hostname del tenant');
        }

        $website = $hostname->website;

        if (!$website) {
            throw new \Exception('No se pudo determinar el website del tenant');
        }

        // Forzar la configuración de la conexión tenant
        $connection = app(\Hyn\Tenancy\Database\Connection::class);
        $connection->set($website);

        // Cambiar la conexión por defecto temporalmente
        config(['database.default' => 'tenant']);

        // Verificar que podemos acceder a la tabla
        try {
            $count = DB::connection('tenant')->table('cuentas_contables')->count();
        } catch (\Exception $e) {
            throw new \Exception('No se pudo establecer conexión con la base de datos del tenant: ' . $e->getMessage());
        }
    }

    // Procesar cuentas en dos fases para manejar jerarquía
    protected function procesarCuentasEnDosFases($cuentasData, $sobreescribir = false)
    {
        $cuentasImportadas = 0;
        $cuentasActualizadas = 0;
        $errores = [];

        // Deshabilitar validaciones del modelo durante la importación
        CuentaContable::$skipValidationOnSaving = true;

        try {
            // Ordenar cuentas por nivel para garantizar que las cuentas padre se creen primero
            usort($cuentasData, function($a, $b) {
                if ($a['nivel'] == $b['nivel']) {
                    // Si tienen el mismo nivel, ordenar por código para mantener consistencia
                    return strcmp($a['codigo'], $b['codigo']);
                }
                return $a['nivel'] - $b['nivel'];
            });

            // Ajustar automáticamente los niveles basados en la estructura jerárquica
            $cuentasData = $this->ajustarNiveles($cuentasData);

            // FASE 1: Crear todas las cuentas sin cuenta_padre_id
            foreach ($cuentasData as $index => $cuentaData) {
                try {
                    // Validar datos básicos
                    if (empty($cuentaData['codigo']) || empty($cuentaData['nombre'])) {
                        $error = "Fila {$cuentaData['fila']}: Código y nombre son obligatorios";
                        $errores[] = $error;
                        continue;
                    }                // Validación especial para cuentas raíz (sin padre)
                if (empty($cuentaData['codigo_padre'])) {
                    // Las cuentas sin padre deben ser nivel 1
                    if ($cuentaData['nivel'] != 1) {
                        $cuentaData['nivel'] = 1;
                    }
                } else {
                    // Para cuentas con padre, validar que el nivel sea coherente
                    // pero no generar error si no lo es, solo advertencia
                    if ($cuentaData['nivel'] <= 1) {
                        // Silently continue
                    }
                }

                // Validar tipo de cuenta
                if (!in_array($cuentaData['tipo_cuenta'], ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'costo'])) {
                    $error = "Fila {$cuentaData['fila']}: Tipo de cuenta inválido: {$cuentaData['tipo_cuenta']}";
                    $errores[] = $error;
                    continue;
                }

                // Validar naturaleza
                if (!in_array($cuentaData['naturaleza'], ['debito', 'credito'])) {
                    $error = "Fila {$cuentaData['fila']}: Naturaleza inválida: {$cuentaData['naturaleza']}";
                    $errores[] = $error;
                    continue;
                }

                // Buscar si ya existe la cuenta
                $cuentaExistente = CuentaContable::on('tenant')->where('codigo', $cuentaData['codigo'])->first();

                // Preparar datos sin cuenta_padre_id
                $datosParaCreacion = [
                    'codigo' => $cuentaData['codigo'],
                    'nombre' => $cuentaData['nombre'],
                    'tipo_cuenta' => $cuentaData['tipo_cuenta'],
                    'naturaleza' => $cuentaData['naturaleza'],
                    'nivel' => $cuentaData['nivel'],
                    'descripcion' => $cuentaData['descripcion'],
                    'activa' => $cuentaData['activa'],
                    'permite_movimiento' => $cuentaData['permite_movimiento'],
                    'requiere_tercero' => $cuentaData['requiere_tercero'],
                    'cuenta_padre_id' => null // Se asignará en fase 2
                ];

                if ($cuentaExistente) {
                    if ($sobreescribir) {
                        $cuentaExistente->update($datosParaCreacion);
                        $cuentasActualizadas++;
                    } else {
                        $error = "Fila {$cuentaData['fila']}: Cuenta {$cuentaData['codigo']} ya existe";
                        $errores[] = $error;
                        continue;
                    }
                } else {
                    $cuenta = new CuentaContable();
                    $cuenta->setConnection('tenant');
                    $cuenta->fill($datosParaCreacion);
                    $cuenta->save();
                    $cuentasImportadas++;
                }

            } catch (\Exception $e) {
                $error = "Fila {$cuentaData['fila']}: " . $e->getMessage();
                $errores[] = $error;
            }
        }

        // FASE 2: Asignar relaciones padre-hijo
        foreach ($cuentasData as $cuentaData) {
            if (!empty($cuentaData['codigo_padre'])) {
                try {
                    $cuenta = CuentaContable::on('tenant')->where('codigo', $cuentaData['codigo'])->first();
                    $cuentaPadre = CuentaContable::on('tenant')->where('codigo', $cuentaData['codigo_padre'])->first();

                    if (!$cuenta) {
                        $error = "Fila {$cuentaData['fila']}: No se pudo encontrar la cuenta {$cuentaData['codigo']} para asignar padre";
                        $errores[] = $error;
                        continue;
                    }

                    if ($cuentaPadre) {
                        $cuenta->cuenta_padre_id = $cuentaPadre->id;
                        $cuenta->save();
                    } else {
                        $error = "Fila {$cuentaData['fila']}: Cuenta padre con código '{$cuentaData['codigo_padre']}' no encontrada";
                        $errores[] = $error;
                    }

                } catch (\Exception $e) {
                    $error = "Fila {$cuentaData['fila']}: Error al asignar padre - " . $e->getMessage();
                    $errores[] = $error;
                }
            }
        }

        } finally {
            // Reestablecer validaciones del modelo
            CuentaContable::$skipValidationOnSaving = false;
        }

        return [
            'cuentasImportadas' => $cuentasImportadas,
            'cuentasActualizadas' => $cuentasActualizadas,
            'errores' => $errores
        ];
    }

    // Helper para normalizar texto eliminando tildes y caracteres especiales
    protected function normalizarTexto($texto)
    {
        $texto = strtolower(trim((string)$texto));

        // Reemplazar caracteres con tildes
        $caracteresConTildes = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ª' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'º' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n'
        ];

        return strtr($texto, $caracteresConTildes);
    }

    // Ajustar automáticamente los niveles basados en la estructura jerárquica
    protected function ajustarNiveles($cuentasData)
    {

        // Crear un mapa de códigos para búsquedas rápidas
        $cuentasPorCodigo = [];
        foreach ($cuentasData as $index => $cuenta) {
            $cuentasPorCodigo[$cuenta['codigo']] = $index;
        }

        $ajustes = 0;

        foreach ($cuentasData as $index => &$cuenta) {
            if (empty($cuenta['codigo_padre'])) {
                // Cuenta sin padre = nivel 1
                if ($cuenta['nivel'] != 1) {
                    $cuenta['nivel'] = 1;
                    $ajustes++;
                }
            } else {
                // Cuenta con padre = calcular nivel basado en el padre
                if (isset($cuentasPorCodigo[$cuenta['codigo_padre']])) {
                    $indicePadre = $cuentasPorCodigo[$cuenta['codigo_padre']];
                    $nivelPadre = $cuentasData[$indicePadre]['nivel'];
                    $nivelEsperado = $nivelPadre + 1;

                    if ($cuenta['nivel'] != $nivelEsperado) {
                        $cuenta['nivel'] = $nivelEsperado;
                        $ajustes++;
                    }
                }
            }
        }

        // Reordenar después de los ajustes
        usort($cuentasData, function($a, $b) {
            if ($a['nivel'] == $b['nivel']) {
                return strcmp($a['codigo'], $b['codigo']);
            }
            return $a['nivel'] - $b['nivel'];
        });

        return $cuentasData;
    }

    /**
     * Get all descendant IDs for a given account
     */
    private function getAllDescendants($accountId)
    {
        $descendants = [];
        $children = CuentaContable::on('tenant')->where('cuenta_padre_id', $accountId)->pluck('id')->toArray();

        foreach ($children as $childId) {
            $descendants[] = $childId;
            $descendants = array_merge($descendants, $this->getAllDescendants($childId));
        }

        return $descendants;
    }

    /**
     * Get tree structure with search functionality
     */
    private function treeWithSearch(Request $request)
    {
        $search = $request->search;

        // Buscar cuentas que coincidan con el criterio
        $matchingAccounts = CuentaContable::on('tenant')
            ->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            })
            ->get();

        if ($matchingAccounts->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Recopilar todas las cuentas relacionadas (ancestros y descendientes)
        $allRelatedIds = [];

        foreach ($matchingAccounts as $account) {
            // Agregar la cuenta actual
            $allRelatedIds[] = $account->id;

            // Agregar todos los ancestros
            $current = $account;
            while ($current && $current->cuenta_padre_id) {
                $allRelatedIds[] = $current->cuenta_padre_id;
                $current = $current->cuentaPadre;
            }

            // Agregar todos los descendientes
            $descendants = $this->getAllDescendants($account->id);
            $allRelatedIds = array_merge($allRelatedIds, $descendants);
        }

        $allRelatedIds = array_unique($allRelatedIds);

        // Obtener todas las cuentas relacionadas
        $allRelatedAccounts = CuentaContable::on('tenant')
            ->whereIn('id', $allRelatedIds)
            ->get()
            ->keyBy('id');

        // Construir la estructura de árbol solo con las cuentas raíz que tienen relación
        $rootAccounts = $allRelatedAccounts->filter(function($account) {
            return is_null($account->cuenta_padre_id);
        })->sortBy('codigo');

        return response()->json([
            'success' => true,
            'data' => $this->buildFilteredTree($rootAccounts, $allRelatedAccounts)
        ]);
    }

    /**
     * Build tree structure with filtered accounts
     */
    private function buildFilteredTree($accounts, $allRelatedAccounts)
    {
        return $accounts->map(function($account) use ($allRelatedAccounts) {
            $accountData = [
                'id' => $account->id,
                'codigo' => $account->codigo,
                'nombre' => $account->nombre,
                'tipo_cuenta' => $account->tipo_cuenta,
                'naturaleza' => $account->naturaleza,
                'nivel' => $account->nivel,
                'activa' => $account->activa,
                'permite_movimiento' => $account->permite_movimiento,
                'saldo_actual' => $account->saldo_actual,
                'es_cuenta_movimiento' => $account->esCuentaMovimiento(),
                'children' => []
            ];

            // Obtener hijos que estén en las cuentas relacionadas
            $children = $allRelatedAccounts->filter(function($child) use ($account) {
                return $child->cuenta_padre_id == $account->id;
            })->sortBy('codigo');

            if ($children->count() > 0) {
                $accountData['children'] = $this->buildFilteredTree($children, $allRelatedAccounts);
            }

            return $accountData;
        })->values();
    }
}
