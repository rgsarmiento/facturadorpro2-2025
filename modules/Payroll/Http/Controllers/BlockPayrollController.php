<?php

namespace Modules\Payroll\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Payroll\Models\{
    DocumentPayroll,
    Worker,
    BlockPayroll
};
use Modules\Payroll\Http\Resources\{
    DocumentPayrollCollection,
    DocumentPayrollResource,
    BlockPayrollCollection,
    BlockPayrollResource
};
use Modules\Payroll\Http\Requests\DocumentPayrollRequest;
use Modules\Factcolombia1\Models\TenantService\{
    PayrollPeriod,
    TypeLawDeductions,
    TypeDisability,
    AdvancedConfiguration,
    TypeOvertimeSurcharge,
};
use Modules\Factcolombia1\Models\Tenant\{
    PaymentMethod,
    TypeDocument,
};
use Illuminate\Support\Facades\DB;
use Exception;
use Modules\Payroll\Helpers\DocumentPayrollHelper;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Modules\Payroll\Traits\UtilityTrait;

class BlockPayrollController extends Controller
{
    use UtilityTrait;

    public function index()
    {
        return view('payroll::block-payrolls.index');
    }

    public function create()
    {
        return view('payroll::block-payrolls.form');
    }

    public function editBlock($id)
    {
        $blockPayroll = BlockPayroll::findOrFail($id);
        
        // Solo permitir edición si el estado es "Registrado" (state_block_id = 1)
        if ($blockPayroll->state_block_id !== 1) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Solo se pueden editar bloques de nómina en estado "Registrado"'], 403);
            }
            return redirect()->route('tenant.block-payrolls.index')->with('error', 'Solo se pueden editar bloques de nómina en estado "Registrado"');
        }
        
        // Si es una petición AJAX, devolver datos JSON
        if (request()->expectsJson()) {
            return response()->json($blockPayroll);
        }
        
        // Si no es AJAX, devolver vista
        return view('payroll::block-payrolls.form', compact('blockPayroll'));
    }

    public function columns()
    {
        return [
            'period' => 'Período',
            'date_of_issue' => 'Fecha de emisión',
        ];
    }

    public function activeWorkers()
    {
        $workers = Worker::where('state', 1)->get();
        $workers = $workers->transform(function($worker) {
            $row = $worker->getRowResource();
            $row['generate_provisions'] = false;
            return $row;
        });
        return response()->json([
            'success' => true,
            'data' => $workers
        ]);
    }

    public function tables()
    {
        $user = auth()->user();
        $ni_resolution_id = $user->ni_resolution_id;

        // Obtener datos del establecimiento
        $establishment = \App\Models\Tenant\Establishment::where('id', $user->establishment_id ?? 1)->first();

        return [
            'workers' => $this->table('workers'),
            'payroll_periods' => PayrollPeriod::get(),
            'type_disabilities' => TypeDisability::get(),
            'payment_methods' => PaymentMethod::get(),
            'type_law_deductions' => TypeLawDeductions::whereTypeLawDeductionsWorker()->get(),
            'advanced_configuration' => AdvancedConfiguration::first(),
            'resolutions' => TypeDocument::select('id','prefix', 'resolution_number')->where('code', 9)->get(),
            'ni_resolution_id' => $ni_resolution_id,
            'establishment_id' => $user->establishment_id ?? 1,
            'establishment' => $establishment, // Datos completos del establecimiento
        ];
    }

    public function table($table)
    {
        if($table == 'workers')
        {
            return Worker::where('state', 1)->take(20)->get()->transform(function($row){
                return $row->getSearchRowResource();
            });
        }

        if($table == 'type_overtime_surcharges')
        {
            return TypeOvertimeSurcharge::get();
        }
        return [];
    }

    public function record($id)
    {
        return BlockPayroll::findOrFail($id);
    }


    public function records(Request $request)
    {
        $records = BlockPayroll::whereFilterRecords($request)->latest();
        return new BlockPayrollCollection($records->paginate(config('tenant.items_per_page')));
    }


    /**
     * Consultar zipkey - usado en habilitación
     *
     * @param  Request $request
     * @return array
     */
    public function queryZipkey(Request $request)
    {

        try {

            $document = DocumentPayroll::findOrFail($request->id);
            $helper = new DocumentPayrollHelper();
            $zip_key = $document->response_api->ResponseDian->Envelope->Body->SendTestSetAsyncResponse->SendTestSetAsyncResult->ZipKey;
            // dd($document);

            return $helper->validateZipKey($zip_key, $document->number_full, $document);


        } catch (Exception $e)
        {
            return $this->getErrorFromException($e->getMessage(), $e);
        }

    }

    public function store(DocumentPayrollRequest $request)
    {
        try {

            $data = DB::connection('tenant')->transaction(function () use($request) {
                $documents = [];
                $workers = $request->worker_id;
                foreach ($workers as $worker_id) {
                    $newRequest = clone $request;
                    $worker_model = Worker::find($worker_id);
                    $newRequest->merge([
                        'worker_id' => $worker_id,
                        'payment' => [
                            'bank_name' => $worker_model->payment->bank_name,
                            'account_type' => $worker_model->payment->account_type,
                            'account_number' => $worker_model->payment->account_number,
                            'payment_method_id' => $worker_model->payment->payment_method_id,
                        ]
                    ]);

                    // inputs
                    $helper = new DocumentPayrollHelper();
                    $inputs = $helper->getInputs($request);

                    // registrar nomina en bd
                    $document = DocumentPayroll::create($inputs);
                    $document->accrued()->create($inputs['accrued']);
                    $document->deduction()->create($inputs['deduction']);

                    // enviar nomina a la api
                    $send_to_api = $helper->sendToApi($document, $inputs);

                    $document->update([
                        'response_api' => $send_to_api
                    ]);
                    $documents[] = $document->id;
                }

                return [
                    'documents' => $documents,
                    'total' => count($documents)
                ];
            });

            return [
                'success' => true,
                'message' => 'Nómina registrada con éxito',
                'data' => $data
            ];

        } catch (Exception $e)
        {
            return $this->getErrorFromException($e->getMessage(), $e);
        }

    }

    /**
     * Guardar bloque de nómina sin generar documentos
     */
    public function storeWithoutGenerate(Request $request)
    {
        try {
            // Validar que no exista un registro con el mismo período ANTES de la transacción
            $existingRecord = null;

            try {
                // Intento 1: Usar columnas virtuales (más eficiente)
                $existingRecord = BlockPayroll::where('period_start_virtual', $request->general_period_start)
                    ->where('period_end_virtual', $request->general_period_end)
                    ->first();
            } catch (Exception $e) {
                // Intento 2: Si falla, usar consultas JSON (fallback)
                $existingRecord = BlockPayroll::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(period, '$.period_start')) = ?", [$request->general_period_start])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(period, '$.period_end')) = ?", [$request->general_period_end])
                    ->first();
            }

            if ($existingRecord) {
                return [
                    'success' => false,
                    'message' => "Ya existe un bloque de nómina para el período del {$request->general_period_start} al {$request->general_period_end}"
                ];
            }

            $data = DB::connection('tenant')->transaction(function () use($request) {

                // Preparar datos del periodo para cada empleado
                $employeePeriodData = [];
                $employeePaymentData = [];
                $workers = $request->selected_workers ?? [];

                // Si employee_period_data viene como objeto anidado, usarlo directamente
                if ($request->has('employee_period_data') && is_array($request->employee_period_data)) {
                    $employeePeriodData = $request->employee_period_data;
                } else {
                    // Fallback: construir desde la estructura de puntos (compatibilidad hacia atrás)
                    foreach ($workers as $workerId) {
                        $periodKey = "employee_period_data.{$workerId}";
                        $employeePeriodData[$workerId] = [
                            'worker_id' => $workerId,
                            'period_start' => $request->input("{$periodKey}.period_start"),
                            'period_end' => $request->input("{$periodKey}.period_end"),
                            'salary' => $request->input("{$periodKey}.salary"),
                            'worked_days' => $request->input("{$periodKey}.worked_days"),
                            // Agregar otros campos del periodo según sea necesario
                        ];
                    }
                }

                // Manejar datos de pago para cada empleado
                if ($request->has('employee_payment_data') && is_array($request->employee_payment_data)) {
                    $employeePaymentData = $request->employee_payment_data;
                }

                // Crear el payload con todos los datos del formulario
                $payload = [
                    'form_data' => [
                        'establishment_id' => $request->establishment_id,
                        'resolution_id' => $request->resolution_id,
                        'date_of_issue' => $request->date_of_issue,
                        'time_of_issue' => $request->time_of_issue,
                        'notes' => $request->notes,
                    ],
                    'selected_workers' => $workers,
                    'employee_period_data' => $employeePeriodData,
                    'employee_payment_data' => $employeePaymentData,
                    'created_at' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                ];

                // Calcular totales (puedes ajustar esta lógica según tus necesidades)
                $accruedTotal = 0;
                $deductionsTotal = 0;

                foreach ($employeePeriodData as $workerData) {
                    $accruedTotal += $workerData['salary'] ?? 0;
                }

                // Obtener establishment_id del usuario si no se proporciona
                $establishmentId = $request->establishment_id ?? auth()->user()->establishment_id ?? 1;

                // Crear el objeto periodo simplificado
                $periodData = [
                    'period_start' => $request->general_period_start,
                    'period_end' => $request->general_period_end
                ];

                // Obtener los datos del establecimiento
                $establishmentData = $request->establishment_data;
                if (!$establishmentData) {
                    $establishment = \App\Models\Tenant\Establishment::where('id', $establishmentId)->first();
                    $establishmentData = $establishment ? $establishment->toArray() : ['id' => $establishmentId, 'description' => 'Establecimiento Principal'];
                }

                // Crear el registro en la tabla co_block_payrolls
                $blockPayroll = BlockPayroll::create([
                    'user_id' => auth()->id(),
                    'date_of_issue' => $request->date_of_issue,
                    'time_of_issue' => $request->time_of_issue ?? now()->format('H:i:s'),
                    'establishment_id' => $establishmentId,
                    'establishment' => $establishmentData,
                    'period' => $periodData,
                    'workers_quantity' => count($workers),
                    'notes' => $request->notes,
                    'accrued_total' => $accruedTotal,
                    'deductions_total' => $deductionsTotal,
                    'payload' => $payload,
                    'resolution_id' => $request->resolution_id,
                    'state_block_id' => 1, // 1 = Registrado
                ]);

                return [
                    'block_payroll_id' => $blockPayroll->id,
                    'workers_count' => count($workers),
                    'accrued_total' => $accruedTotal,
                ];
            });

            return [
                'success' => true,
                'message' => 'Bloque de nómina guardado exitosamente sin generar documentos',
                'data' => $data
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al guardar el bloque de nómina: ' . $e->getMessage()
            ];
        }
    }


    /**
     * Descarga de xml/pdf
     *
     * @param  string $filename
     */
    public function downloadFile($filename)
    {
        return app(DocumentController::class)->downloadFile($filename);
    }


    /**
     * Envio de correo de la nómina
     *
     * @param  Request $request
     * @return array
     */
    public function sendEmail(Request $request)
    {
        return (new DocumentPayrollHelper())->sendEmail($request);
    }

    /**
     * Actualizar un bloque de nómina existente
     * 
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function updateBlock(Request $request, $id)
    {
        try {
            $blockPayroll = BlockPayroll::findOrFail($id);
            
            // Solo permitir actualización si el estado es "Registrado" (state_block_id = 1)
            if ($blockPayroll->state_block_id !== 1) {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden editar bloques de nómina en estado "Registrado"'
                ];
            }

            $data = DB::connection('tenant')->transaction(function () use ($request, $blockPayroll) {
                // Preparar datos del periodo para cada empleado
                $employeePeriodData = [];
                $employeePaymentData = [];
                $workers = $request->selected_workers ?? [];

                // Si employee_period_data viene como objeto anidado, usarlo directamente
                if ($request->has('employee_period_data') && is_array($request->employee_period_data)) {
                    $employeePeriodData = $request->employee_period_data;
                } else {
                    // Fallback: construir desde la estructura de puntos (compatibilidad hacia atrás)
                    foreach ($workers as $workerId) {
                        $periodKey = "employee_period_data.{$workerId}";
                        $employeePeriodData[$workerId] = [
                            'worker_id' => $workerId,
                            'period_start' => $request->input("{$periodKey}.period_start"),
                            'period_end' => $request->input("{$periodKey}.period_end"),
                            'salary' => $request->input("{$periodKey}.salary"),
                            'worked_days' => $request->input("{$periodKey}.worked_days"),
                        ];
                    }
                }

                // Manejar datos de pago para cada empleado
                if ($request->has('employee_payment_data') && is_array($request->employee_payment_data)) {
                    $employeePaymentData = $request->employee_payment_data;
                }

                // Crear el payload con todos los datos del formulario
                $payload = [
                    'form_data' => [
                        'establishment_id' => $request->establishment_id,
                        'resolution_id' => $request->resolution_id,
                        'date_of_issue' => $request->date_of_issue,
                        'time_of_issue' => $request->time_of_issue,
                        'notes' => $request->notes,
                    ],
                    'selected_workers' => $workers,
                    'employee_period_data' => $employeePeriodData,
                    'employee_payment_data' => $employeePaymentData,
                    'updated_at' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                ];

                // Calcular totales (puedes ajustar esta lógica según tus necesidades)
                $accruedTotal = 0;
                $deductionsTotal = 0;

                foreach ($employeePeriodData as $workerData) {
                    $accruedTotal += $workerData['salary'] ?? 0;
                }

                // Obtener establishment_id del usuario si no se proporciona
                $establishmentId = $request->establishment_id ?? auth()->user()->establishment_id ?? 1;

                // Crear el objeto periodo simplificado
                $periodData = [
                    'period_start' => $request->general_period_start,
                    'period_end' => $request->general_period_end
                ];

                // Obtener los datos del establecimiento
                $establishmentData = $request->establishment_data;
                if (!$establishmentData) {
                    $establishment = \App\Models\Tenant\Establishment::where('id', $establishmentId)->first();
                    $establishmentData = $establishment ? $establishment->toArray() : ['id' => $establishmentId, 'description' => 'Establecimiento Principal'];
                }

                // Actualizar el registro en la tabla co_block_payrolls
                $blockPayroll->update([
                    'date_of_issue' => $request->date_of_issue,
                    'time_of_issue' => $request->time_of_issue ?? now()->format('H:i:s'),
                    'establishment_id' => $establishmentId,
                    'establishment' => $establishmentData,
                    'period' => $periodData,
                    'workers_quantity' => count($workers),
                    'notes' => $request->notes,
                    'accrued_total' => $accruedTotal,
                    'deductions_total' => $deductionsTotal,
                    'payload' => $payload,
                    'resolution_id' => $request->resolution_id,
                ]);

                return [
                    'block_payroll_id' => $blockPayroll->id,
                    'workers_count' => count($workers),
                    'accrued_total' => $accruedTotal,
                ];
            });

            return [
                'success' => true,
                'message' => 'Bloque de nómina actualizado exitosamente',
                'data' => $data
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar el bloque de nómina: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si ya existe un período registrado
     */
    public function checkPeriodExists(Request $request)
    {
        try {
            $periodStart = $request->get('period_start');
            $periodEnd = $request->get('period_end');

            if (!$periodStart || !$periodEnd) {
                return response()->json([
                    'exists' => false
                ]);
            }

            // Buscar bloques existentes que se traslapen con el período solicitado
            $exists = BlockPayroll::where(function ($query) use ($periodStart, $periodEnd) {
                // Caso 1: El período solicitado está completamente dentro de un período existente
                $query->where(function ($subQuery) use ($periodStart, $periodEnd) {
                    $subQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_start")) <= ?', [$periodStart])
                             ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_end")) >= ?', [$periodEnd]);
                });
                
                // Caso 2: Un período existente está completamente dentro del período solicitado
                $query->orWhere(function ($subQuery) use ($periodStart, $periodEnd) {
                    $subQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_start")) >= ?', [$periodStart])
                             ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_end")) <= ?', [$periodEnd]);
                });
                
                // Caso 3: El período solicitado se traslapa por el inicio
                $query->orWhere(function ($subQuery) use ($periodStart, $periodEnd) {
                    $subQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_start")) <= ?', [$periodStart])
                             ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_end")) >= ?', [$periodStart])
                             ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_end")) <= ?', [$periodEnd]);
                });
                
                // Caso 4: El período solicitado se traslapa por el final
                $query->orWhere(function ($subQuery) use ($periodStart, $periodEnd) {
                    $subQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_start")) >= ?', [$periodStart])
                             ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_start")) <= ?', [$periodEnd])
                             ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.general_period_end")) >= ?', [$periodEnd]);
                });
            })->exists();

            return response()->json([
                'exists' => $exists
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'exists' => false // En caso de error, permitir continuar
            ]);
        }
    }

}
