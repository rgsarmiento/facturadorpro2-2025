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
            // Generar archivo Excel usando Laravel Excel
            $filename = 'plantilla_puc_' . date('Y-m-d') . '.xlsx';

            return Excel::download(new \App\Exports\PucTemplateExport, $filename);
        } else {
            // Generar archivo CSV
            $filename = 'plantilla_puc_' . date('Y-m-d') . '.csv';

            $headers = [
                'codigo',
                'nombre',
                'tipo_cuenta',
                'naturaleza',
                'nivel',
                'cuenta_padre_id',
                'descripcion',
                'activa',
                'permite_movimiento'
            ];

            // Datos de ejemplo básicos del PUC colombiano
            $ejemplos = [
                ['1', 'ACTIVO', 'activo', 'debito', '1', '', 'Representa todos los bienes y derechos de la empresa', '1', '0'],
                ['11', 'DISPONIBLE', 'activo', 'debito', '2', '1', 'Efectivo y equivalentes de efectivo', '1', '0'],
                ['1105', 'CAJA', 'activo', 'debito', '3', '11', 'Dinero en efectivo en caja', '1', '1'],
                ['110505', 'CAJA GENERAL', 'activo', 'debito', '4', '1105', 'Caja principal de la empresa', '1', '1'],
                ['110510', 'CAJAS MENORES', 'activo', 'debito', '4', '1105', 'Cajas menores para gastos menores', '1', '1'],
                ['1110', 'BANCOS', 'activo', 'debito', '3', '11', 'Cuentas corrientes y de ahorros en bancos', '1', '0'],
                ['111005', 'MONEDA NACIONAL', 'activo', 'debito', '4', '1110', 'Cuentas bancarias en pesos colombianos', '1', '1'],
                ['111010', 'MONEDA EXTRANJERA', 'activo', 'debito', '4', '1110', 'Cuentas bancarias en moneda extranjera', '1', '1'],
                ['12', 'INVERSIONES', 'activo', 'debito', '2', '1', 'Inversiones temporales y permanentes', '1', '0'],
                ['1205', 'ACCIONES', 'activo', 'debito', '3', '12', 'Inversiones en acciones', '1', '1'],
                ['13', 'DEUDORES', 'activo', 'debito', '2', '1', 'Cuentas por cobrar a terceros', '1', '0'],
                ['1305', 'CLIENTES', 'activo', 'debito', '3', '13', 'Cuentas por cobrar a clientes', '1', '1'],
                ['130505', 'CLIENTES NACIONALES', 'activo', 'debito', '4', '1305', 'Clientes del territorio nacional', '1', '1'],
                ['130510', 'CLIENTES DEL EXTERIOR', 'activo', 'debito', '4', '1305', 'Clientes del exterior', '1', '1'],
                ['14', 'INVENTARIOS', 'activo', 'debito', '2', '1', 'Mercancías y materias primas', '1', '0'],
                ['1435', 'MERCANCÍAS NO FABRICADAS POR LA EMPRESA', 'activo', 'debito', '3', '14', 'Productos para la venta no fabricados', '1', '1'],
                ['15', 'PROPIEDADES PLANTA Y EQUIPO', 'activo', 'debito', '2', '1', 'Activos fijos tangibles', '1', '0'],
                ['1504', 'TERRENOS', 'activo', 'debito', '3', '15', 'Terrenos de propiedad de la empresa', '1', '1'],
                ['1516', 'CONSTRUCCIONES Y EDIFICACIONES', 'activo', 'debito', '3', '15', 'Edificios y construcciones', '1', '1'],
                ['1520', 'MAQUINARIA Y EQUIPO', 'activo', 'debito', '3', '15', 'Maquinaria y equipos de producción', '1', '1'],
                ['1524', 'EQUIPO DE OFICINA', 'activo', 'debito', '3', '15', 'Equipos y muebles de oficina', '1', '1'],
                ['1528', 'EQUIPO DE COMPUTACIÓN Y COMUNICACIÓN', 'activo', 'debito', '3', '15', 'Computadores y equipos de comunicación', '1', '1'],
                ['2', 'PASIVO', 'pasivo', 'credito', '1', '', 'Obligaciones y deudas de la empresa', '1', '0'],
                ['21', 'OBLIGACIONES FINANCIERAS', 'pasivo', 'credito', '2', '2', 'Préstamos y obligaciones bancarias', '1', '0'],
                ['2105', 'BANCOS NACIONALES', 'pasivo', 'credito', '3', '21', 'Obligaciones con bancos nacionales', '1', '1'],
                ['22', 'PROVEEDORES', 'pasivo', 'credito', '2', '2', 'Cuentas por pagar a proveedores', '1', '0'],
                ['2205', 'PROVEEDORES NACIONALES', 'pasivo', 'credito', '3', '22', 'Deudas con proveedores nacionales', '1', '1'],
                ['3', 'PATRIMONIO', 'patrimonio', 'credito', '1', '', 'Capital y utilidades de los socios', '1', '0'],
                ['31', 'CAPITAL SOCIAL', 'patrimonio', 'credito', '2', '3', 'Aportes de los socios', '1', '0'],
                ['3115', 'APORTES SOCIALES', 'patrimonio', 'credito', '3', '31', 'Capital aportado por socios', '1', '1'],
                ['4', 'INGRESOS', 'ingreso', 'credito', '1', '', 'Ingresos operacionales y no operacionales', '1', '0'],
                ['41', 'OPERACIONALES', 'ingreso', 'credito', '2', '4', 'Ingresos por la actividad principal', '1', '0'],
                ['4135', 'COMERCIO AL POR MAYOR Y AL POR MENOR', 'ingreso', 'credito', '3', '41', 'Ventas de mercancías', '1', '1'],
                ['5', 'GASTOS', 'gasto', 'debito', '1', '', 'Gastos operacionales y no operacionales', '1', '0'],
                ['51', 'OPERACIONALES DE ADMINISTRACIÓN', 'gasto', 'debito', '2', '5', 'Gastos de administración', '1', '0'],
                ['5105', 'GASTOS DE PERSONAL', 'gasto', 'debito', '3', '51', 'Gastos relacionados con personal', '1', '0'],
                ['510506', 'SUELDOS', 'gasto', 'debito', '4', '5105', 'Sueldos de empleados', '1', '1'],
                ['6', 'COSTOS DE VENTAS', 'costo', 'debito', '1', '', 'Costos directos de la mercancía vendida', '1', '0'],
                ['61', 'COSTO DE VENTAS Y DE PRESTACIÓN DE SERVICIOS', 'costo', 'debito', '2', '6', 'Costo de productos vendidos', '1', '0'],
                ['6135', 'COMERCIO AL POR MAYOR Y AL POR MENOR', 'costo', 'debito', '3', '61', 'Costo de mercancías vendidas', '1', '1']
            ];

            return response()->streamDownload(function() use ($headers, $ejemplos) {
                $file = fopen('php://output', 'w');

                // Escribir BOM para UTF-8
                fwrite($file, "\xEF\xBB\xBF");

                // Escribir encabezados
                fputcsv($file, $headers);

                // Escribir ejemplos
                foreach ($ejemplos as $ejemplo) {
                    fputcsv($file, $ejemplo);
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
        \Log::info('CuentaContable columns requested');

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
            \Log::info('CuentaContable records request:', $request->all());

            $query = CuentaContable::query();

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
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
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
            \Log::error('Error in CuentaContable records:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error al obtener los registros',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get records in tree structure
     */
    public function tree()
    {
        $cuentasRaiz = CuentaContable::raiz()
            ->activas()
            ->with(['descendientes' => function($query) {
                $query->where('activa', true);
            }])
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $this->buildTree($cuentasRaiz)
        ]);
    }

    /**
     * Build hierarchical tree structure
     */
    private function buildTree($cuentas)
    {
        return $cuentas->map(function($cuenta) {
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
                'children' => $this->buildTree($cuenta->cuentasHijas()->activas()->orderBy('codigo')->get())
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
            DB::beginTransaction();

            // Validaciones básicas
            $rules = CuentaContable::rules();
            $request->validate($rules);

            // Validaciones adicionales de negocio
            $this->validarReglasNegocio($request);

            $cuenta = new CuentaContable();
            $cuenta->fill($request->all());

            // Auto-asignar naturaleza si no se especifica
            if (!$cuenta->naturaleza && $cuenta->tipo_cuenta) {
                $cuenta->naturaleza = CuentaContable::getNaturalezaPorTipo($cuenta->tipo_cuenta);
            }

            $cuenta->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta contable creada exitosamente',
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
            $cuentaPadre = CuentaContable::find($request->cuenta_padre_id);
            if ($cuentaPadre) {
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

            if (in_array($extension, ['csv', 'txt'])) {
                // Procesar archivo CSV
                $handle = fopen($file->getPathname(), 'r');

                if ($handle === false) {
                    throw new \Exception('No se pudo abrir el archivo');
                }

                // Leer primera fila (encabezados)
                $headers = fgetcsv($handle);
                if (!$headers) {
                    throw new \Exception('El archivo está vacío o no tiene el formato correcto');
                }

                // Mapear encabezados esperados
                $expectedHeaders = [
                    'codigo', 'nombre', 'tipo_cuenta', 'naturaleza', 'nivel',
                    'cuenta_padre_id', 'descripcion', 'activa', 'permite_movimiento'
                ];

                $rowNumber = 1;
                while (($data = fgetcsv($handle)) !== false) {
                    $rowNumber++;

                    if (count($data) < count($expectedHeaders)) {
                        $errores[] = "Fila $rowNumber: Faltan columnas";
                        continue;
                    }

                    try {
                        $cuentaData = [
                            'codigo' => trim($data[0]),
                            'nombre' => trim($data[1]),
                            'tipo_cuenta' => trim($data[2]),
                            'naturaleza' => trim($data[3]),
                            'nivel' => intval($data[4]),
                            'cuenta_padre_id' => !empty(trim($data[5])) ? trim($data[5]) : null,
                            'descripcion' => trim($data[6] ?? ''),
                            'activa' => boolval($data[7] ?? 1),
                            'permite_movimiento' => boolval($data[8] ?? 1)
                        ];

                        // Validar datos básicos
                        if (empty($cuentaData['codigo']) || empty($cuentaData['nombre'])) {
                            $errores[] = "Fila $rowNumber: Código y nombre son obligatorios";
                            continue;
                        }

                        // Validar tipo de cuenta
                        if (!in_array($cuentaData['tipo_cuenta'], ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'costo'])) {
                            $errores[] = "Fila $rowNumber: Tipo de cuenta inválido: {$cuentaData['tipo_cuenta']}";
                            continue;
                        }

                        // Validar naturaleza
                        if (!in_array($cuentaData['naturaleza'], ['debito', 'credito'])) {
                            $errores[] = "Fila $rowNumber: Naturaleza inválida: {$cuentaData['naturaleza']}";
                            continue;
                        }

                        // Buscar si ya existe la cuenta
                        $cuentaExistente = CuentaContable::where('codigo', $cuentaData['codigo'])->first();

                        if ($cuentaExistente) {
                            if ($sobreescribir) {
                                $cuentaExistente->update($cuentaData);
                                $cuentasActualizadas++;
                            } else {
                                $errores[] = "Fila $rowNumber: Cuenta {$cuentaData['codigo']} ya existe";
                                continue;
                            }
                        } else {
                            CuentaContable::create($cuentaData);
                            $cuentasImportadas++;
                        }

                    } catch (\Exception $e) {
                        $errores[] = "Fila $rowNumber: " . $e->getMessage();
                    }
                }

                fclose($handle);

            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                // Procesar archivo Excel usando Laravel Excel
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

                        try {
                            $cuentaData = [
                                'codigo' => trim((string)$row[0]),
                                'nombre' => trim((string)$row[1]),
                                'tipo_cuenta' => trim((string)$row[2]),
                                'naturaleza' => trim((string)$row[3]),
                                'nivel' => intval($row[4]),
                                'cuenta_padre_id' => !empty(trim((string)$row[5])) ? trim((string)$row[5]) : null,
                                'descripcion' => trim((string)($row[6] ?? '')),
                                'activa' => boolval($row[7] ?? 1),
                                'permite_movimiento' => boolval($row[8] ?? 1)
                            ];

                            // Validar datos básicos
                            if (empty($cuentaData['codigo']) || empty($cuentaData['nombre'])) {
                                $errores[] = "Fila $rowNumber: Código y nombre son obligatorios";
                                continue;
                            }

                            // Validar tipo de cuenta
                            if (!in_array($cuentaData['tipo_cuenta'], ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'costo'])) {
                                $errores[] = "Fila $rowNumber: Tipo de cuenta inválido: {$cuentaData['tipo_cuenta']}";
                                continue;
                            }

                            // Validar naturaleza
                            if (!in_array($cuentaData['naturaleza'], ['debito', 'credito'])) {
                                $errores[] = "Fila $rowNumber: Naturaleza inválida: {$cuentaData['naturaleza']}";
                                continue;
                            }

                            // Buscar si ya existe la cuenta
                            $cuentaExistente = CuentaContable::where('codigo', $cuentaData['codigo'])->first();

                            if ($cuentaExistente) {
                                if ($sobreescribir) {
                                    $cuentaExistente->update($cuentaData);
                                    $cuentasActualizadas++;
                                } else {
                                    $errores[] = "Fila $rowNumber: Cuenta {$cuentaData['codigo']} ya existe";
                                    continue;
                                }
                            } else {
                                CuentaContable::create($cuentaData);
                                $cuentasImportadas++;
                            }

                        } catch (\Exception $e) {
                            $errores[] = "Fila $rowNumber: " . $e->getMessage();
                        }
                    }

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
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'importadas' => $cuentasImportadas,
                    'actualizadas' => $cuentasActualizadas,
                    'errores' => $errores
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
}
