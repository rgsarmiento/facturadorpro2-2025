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
use Illuminate\Support\Facades\Log;
use Exception;
use Modules\Payroll\Helpers\DocumentPayrollHelper;
use Modules\Factcolombia1\Http\Controllers\Tenant\DocumentController;
use Modules\Factcolombia1\Helpers\HttpConnectionApi;
use PDF;
use Modules\Factcolombia1\Models\TenantService\Company;
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

    /**
     * Mostrar un bloque específico con sus relaciones
     */
    public function show($id)
    {
        $blockPayroll = BlockPayroll::with(['state_block'])
            ->findOrFail($id);

        // Obtener los datos con formato similar al usado en getRowCollection
        $data = $blockPayroll->getRowResource();

        // Agregar información adicional del estado
        if ($blockPayroll->state_block) {
            $data['state_block_name'] = $blockPayroll->state_block->name;
        } else {
            $data['state_block_name'] = 'Sin estado';
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Generar PDF del bloque de nómina
     */
    public function generatePDF($id)
    {
        try {
            $blockPayroll = BlockPayroll::with(['state_block'])
                ->findOrFail($id);

            // Obtener los datos del JSON almacenado
            $payrollData = $blockPayroll->block_payroll_json;

            if (!$payrollData) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay datos de nómina para generar el PDF. Debe guardar y generar primero.'
                ], 400);
            }

            // Si payrollData es string, decodificar
            if (is_string($payrollData)) {
                $employeesData = json_decode($payrollData, true);
            } else {
                $employeesData = $payrollData;
            }

            if (!$employeesData || !is_array($employeesData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los datos de nómina no tienen un formato válido.'
                ], 400);
            }

            // Verificar si el estado es diferente a "Aceptado" (state_block_id != 5)
            $showWatermark = $blockPayroll->state_block_id != 5;

            // Generar el PDF
            $pdf = $this->createPayrollPDF($blockPayroll, $employeesData, $showWatermark);

            $filename = "bloque_nomina_{$blockPayroll->id}.pdf";

            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Error generando PDF de nómina: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el PDF: ' . $e->getMessage()
            ], 500);
        }
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

    /**
     * Consulta individual de ZipKey para obtener consecutivo desde datos almacenados
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryIndividualZipkey(Request $request)
    {
        try {
            $block_payroll_id = $request->input('block_payroll_id');
            $worker_id = $request->input('worker_id');

            // Log para debugging
            Log::info("queryIndividualZipkey called", [
                'block_payroll_id' => $block_payroll_id,
                'worker_id' => $worker_id
            ]);

            $blockPayroll = BlockPayroll::find($block_payroll_id);
            if (!$blockPayroll) {
                return response()->json(['success' => false, 'message' => 'Bloque de nómina no encontrado'], 404);
            }

            // Obtener las respuestas JSON del bloque
            $jsonResponses = $blockPayroll->block_payroll_json_responses;
            if (!$jsonResponses) {
                return response()->json(['success' => false, 'message' => 'No hay respuestas JSON disponibles'], 404);
            }

            // Log de la estructura de datos
            Log::info("JSON Responses structure", [
                'keys' => array_keys($jsonResponses),
                'worker_ids_available' => array_keys($jsonResponses)
            ]);

            // Buscar la respuesta para el worker_id específico
            $workerResponse = null;
            foreach ($jsonResponses as $workerId => $response) {
                if ($workerId == $worker_id) {
                    $workerResponse = $response;
                    break;
                }
            }

            if (!$workerResponse) {
                Log::info("Worker response not found", [
                    'looking_for_worker_id' => $worker_id,
                    'available_worker_ids' => array_keys($jsonResponses)
                ]);
                return response()->json(['success' => false, 'message' => 'Respuesta no encontrada para este trabajador'], 404);
            }

            // Log de la respuesta del trabajador
            Log::info("Worker response found", $workerResponse);

            // Extraer información directamente de la respuesta almacenada
            $zipKey = $workerResponse['zip_key'] ?? '';
            $isValid = $workerResponse['is_valid'] ?? false;
            $consecutiveNumber = '';
            $statusMessage = '';

            // Intentar extraer el consecutivo de múltiples fuentes disponibles
            // 1. Del mensaje de zipkey_query_response
            if (isset($workerResponse['zipkey_query_response']['message'])) {
                $statusMessage = $workerResponse['zipkey_query_response']['message'];
                if (preg_match('/NI-?(\d+)/', $statusMessage, $matches)) {
                    $consecutiveNumber = $matches[1];
                    Log::info("Consecutive found in zipkey_query_response message", ['consecutive' => $consecutiveNumber]);
                }
            }

            // 2. Del campo urlpayrollpdf si no se encontró en el mensaje
            if (empty($consecutiveNumber) && isset($workerResponse['urlpayrollpdf'])) {
                $pdfUrl = $workerResponse['urlpayrollpdf'];
                if (preg_match('/NI(\d+)\.pdf/', $pdfUrl, $matches)) {
                    $consecutiveNumber = $matches[1];
                }
            }

            // 3. Del campo message si no se encontró anteriormente
            if (empty($consecutiveNumber) && isset($workerResponse['message'])) {
                $message = $workerResponse['message'];
                if (preg_match('/NI(\d+)/', $message, $matches)) {
                    $consecutiveNumber = $matches[1];
                }
            }

            // 4. Buscar en la respuesta DIAN original si está disponible
            if (empty($consecutiveNumber) && isset($workerResponse['original_api_response']['ResponseDian'])) {
                $dianResponse = $workerResponse['original_api_response']['ResponseDian'];
                // Buscar en diferentes niveles de la respuesta DIAN
                $this->extractConsecutiveFromDianResponse($dianResponse, $consecutiveNumber);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'zip_key' => $zipKey,
                    'is_valid' => $isValid,
                    'status_message' => $statusMessage,
                    'consecutive_number' => $consecutiveNumber,
                    'worker_id' => $worker_id,
                    'worker_name' => $workerResponse['worker_name'] ?? '',
                    'processed_at' => $workerResponse['processed_at'] ?? ''
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error in queryIndividualZipkey: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener todos los consecutivos de un bloque de nómina de una vez
     */
    public function queryAllConsecutives(Request $request)
    {
        try {
            $block_payroll_id = $request->input('block_payroll_id');

            $blockPayroll = BlockPayroll::find($block_payroll_id);
            if (!$blockPayroll) {
                return response()->json(['success' => false, 'message' => 'Bloque de nómina no encontrado'], 404);
            }

            // Obtener las respuestas JSON del bloque
            $jsonResponses = $blockPayroll->block_payroll_json_responses;
            if (!$jsonResponses) {
                return response()->json(['success' => false, 'message' => 'No hay respuestas JSON disponibles'], 404);
            }

            $results = [];
            $processed = 0;
            $found = 0;

            foreach ($jsonResponses as $workerId => $workerResponse) {
                $consecutiveNumber = '';
                $zipKey = $workerResponse['zip_key'] ?? '';
                $isValid = $workerResponse['is_valid'] ?? false;
                $statusMessage = '';

                // Aplicar la misma lógica de extracción que en queryIndividualZipkey
                // 1. Del mensaje de zipkey_query_response
                if (isset($workerResponse['zipkey_query_response']['message'])) {
                    $statusMessage = $workerResponse['zipkey_query_response']['message'];
                    if (preg_match('/NI-?(\d+)/', $statusMessage, $matches)) {
                        $consecutiveNumber = $matches[1];
                    }
                }

                // 2. Del campo urlpayrollpdf si no se encontró en el mensaje
                if (empty($consecutiveNumber) && isset($workerResponse['urlpayrollpdf'])) {
                    $pdfUrl = $workerResponse['urlpayrollpdf'];
                    if (preg_match('/NI(\d+)\.pdf/', $pdfUrl, $matches)) {
                        $consecutiveNumber = $matches[1];
                    }
                }

                // 3. Del campo message si no se encontró anteriormente
                if (empty($consecutiveNumber) && isset($workerResponse['message'])) {
                    $message = $workerResponse['message'];
                    if (preg_match('/NI(\d+)/', $message, $matches)) {
                        $consecutiveNumber = $matches[1];
                    }
                }

                // 4. Buscar en la respuesta DIAN original si está disponible
                if (empty($consecutiveNumber) && isset($workerResponse['original_api_response']['ResponseDian'])) {
                    $dianResponse = $workerResponse['original_api_response']['ResponseDian'];
                    $this->extractConsecutiveFromDianResponse($dianResponse, $consecutiveNumber);
                }

                $results[$workerId] = [
                    'worker_id' => $workerId,
                    'consecutive_number' => $consecutiveNumber ?: 'N/A',
                    'zip_key' => $zipKey,
                    'is_valid' => $isValid,
                    'status_message' => $statusMessage,
                    'worker_name' => $workerResponse['worker_name'] ?? ''
                ];

                $processed++;
                if (!empty($consecutiveNumber)) {
                    $found++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $results,
                'meta' => [
                    'total_workers' => count($jsonResponses),
                    'processed' => $processed,
                    'found' => $found
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error in queryAllConsecutives: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Función auxiliar para extraer consecutivo de respuesta DIAN
     */
    private function extractConsecutiveFromDianResponse($dianResponse, &$consecutiveNumber)
    {
        if (is_array($dianResponse)) {
            foreach ($dianResponse as $key => $value) {
                if (is_string($value) && preg_match('/NI-?(\d+)/', $value, $matches)) {
                    $consecutiveNumber = $matches[1];
                    return;
                }
                if (is_array($value)) {
                    $this->extractConsecutiveFromDianResponse($value, $consecutiveNumber);
                    if (!empty($consecutiveNumber)) {
                        return;
                    }
                }
            }
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
                $employeeAccruedData = [];
                $employeeDeductionData = [];
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

                // Manejar datos de devengados para cada empleado
                if ($request->has('employee_accrued_data') && is_array($request->employee_accrued_data)) {
                    $employeeAccruedData = $request->employee_accrued_data;

                    // Debug: verificar que los datos de devengados lleguen correctamente
                    \Log::info('=== DEBUGGING ACCRUED DATA IN storeWithoutGenerate ===');
                    \Log::info('Employee Accrued Data received:', ['data' => $employeeAccruedData]);
                    \Log::info('Number of workers with accrued data: ' . count($employeeAccruedData));
                    foreach ($employeeAccruedData as $workerId => $data) {
                        \Log::info("Worker {$workerId} accrued_total: " . ($data['accrued_total'] ?? 'not set'));
                    }
                }

                // Manejar datos de deducciones para cada empleado
                if ($request->has('employee_deduction_data') && is_array($request->employee_deduction_data)) {
                    $employeeDeductionData = $request->employee_deduction_data;

                    // Debug: verificar que los datos de deducciones lleguen correctamente
                    \Log::info('=== DEBUGGING DEDUCTION DATA IN storeWithoutGenerate ===');
                    \Log::info('Employee Deduction Data received:', ['data' => $employeeDeductionData]);
                    \Log::info('Number of workers with deduction data: ' . count($employeeDeductionData));
                    foreach ($employeeDeductionData as $workerId => $data) {
                        \Log::info("Worker {$workerId} deductions_total: " . ($data['deductions_total'] ?? 'not set'));
                    }
                } else {
                    $employeeDeductionData = [];
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
                    'employee_accrued_data' => $employeeAccruedData,
                    'employee_deduction_data' => $employeeDeductionData,
                    'created_at' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                ];

                // Calcular totales usando los datos de devengados cuando estén disponibles
                $accruedTotal = 0;
                $deductionsTotal = 0;

                // Si hay datos de devengados, usarlos para el cálculo
                if (!empty($employeeAccruedData)) {
                    foreach ($employeeAccruedData as $workerId => $accruedData) {
                        $accruedTotal += $accruedData['accrued_total'] ?? 0;
                    }
                } else {
                    // Fallback: usar datos básicos del periodo
                    foreach ($employeePeriodData as $workerData) {
                        $accruedTotal += $workerData['salary'] ?? 0;
                    }
                }

                // Si hay datos de deducciones, usarlos para el cálculo
                if (!empty($employeeDeductionData)) {
                    foreach ($employeeDeductionData as $workerId => $deductionData) {
                        $deductionsTotal += $deductionData['deductions_total'] ?? 0;
                    }
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
                $employeeAccruedData = [];
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

                // Manejar datos de devengados para cada empleado
                if ($request->has('employee_accrued_data') && is_array($request->employee_accrued_data)) {
                    $employeeAccruedData = $request->employee_accrued_data;

                    // Debug: verificar que los datos de devengados lleguen correctamente
                    \Log::info('=== DEBUGGING ACCRUED DATA IN updateBlock ===');
                    \Log::info('Employee Accrued Data received:', ['data' => $employeeAccruedData]);
                    \Log::info('Number of workers with accrued data: ' . count($employeeAccruedData));
                    foreach ($employeeAccruedData as $workerId => $data) {
                        \Log::info("Worker {$workerId} accrued_total: " . ($data['accrued_total'] ?? 'not set'));
                    }
                }

                // Manejar datos de deducciones para cada empleado
                $employeeDeductionData = [];
                if ($request->has('employee_deduction_data') && is_array($request->employee_deduction_data)) {
                    $employeeDeductionData = $request->employee_deduction_data;
                    \Log::info('=== DEBUGGING DEDUCTION DATA IN updateBlock ===');
                    \Log::info('Employee Deduction Data received:', ['data' => $employeeDeductionData]);
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
                    'employee_accrued_data' => $employeeAccruedData,
                    'employee_deduction_data' => $employeeDeductionData,
                    'updated_at' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                ];

                // Calcular totales usando los datos de devengados cuando estén disponibles
                $accruedTotal = 0;
                $deductionsTotal = 0;

                // Si hay datos de devengados, usarlos para el cálculo
                if (!empty($employeeAccruedData)) {
                    foreach ($employeeAccruedData as $workerId => $accruedData) {
                        $accruedTotal += $accruedData['accrued_total'] ?? 0;
                    }
                } else {
                    // Fallback: usar datos básicos del periodo
                    foreach ($employeePeriodData as $workerData) {
                        $accruedTotal += $workerData['salary'] ?? 0;
                    }
                }

                // Si hay datos de deducciones, usarlos para el cálculo
                if (!empty($employeeDeductionData)) {
                    foreach ($employeeDeductionData as $workerId => $deductionData) {
                        $deductionsTotal += $deductionData['deductions_total'] ?? 0;
                    }
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

    /**
     * Guardar y generar documentos de nómina individuales con progreso en tiempo real
     */
    public function saveAndGenerate(Request $request, $id = null)
    {
        try {
            Log::info("Iniciando saveAndGenerate - ID: " . ($id ?? 'nuevo'));

            // Determinar si es modo edición
            $editMode = !is_null($id);

            Log::info("Modo edición: " . ($editMode ? 'true' : 'false'));

            // Primero guardar el bloque sin generar (reutilizar lógica existente)
            $saveResult = $editMode ? $this->updateBlock($request, $id) : $this->storeWithoutGenerate($request);

            Log::info("Resultado del save: " . json_encode($saveResult));

            if (!$saveResult['success']) {
                return $saveResult;
            }

            $blockPayrollId = $saveResult['data']['block_payroll_id'];
            $blockPayroll = BlockPayroll::findOrFail($blockPayrollId);

            Log::info("Block Payroll cargado: ID " . $blockPayrollId);

            // Obtener datos para el procesamiento
            $selectedWorkers = $request->selected_workers ?? [];
            $totalWorkers = count($selectedWorkers);

            Log::info("Total workers seleccionados: " . $totalWorkers);

            if ($totalWorkers === 0) {
                return [
                    'success' => false,
                    'message' => 'No hay trabajadores para procesar'
                ];
            }

            // Preparar los JSONs individuales para cada empleado
            $employeeJsons = [];
            $apiResponses = [];
            $generatedDocuments = [];
            $errors = [];
            $processedCount = 0;

            $data = DB::connection('tenant')->transaction(function () use (
                $request, $blockPayroll, &$employeeJsons, &$apiResponses, &$generatedDocuments,
                &$errors, &$processedCount, $totalWorkers
            ) {
                Log::info("Iniciando transacción DB");

                $selectedWorkers = $request->selected_workers ?? [];
                $employeePeriodData = $request->employee_period_data ?? [];
                $employeePaymentData = $request->employee_payment_data ?? [];
                $employeeAccruedData = $request->employee_accrued_data ?? [];
                $employeeDeductionData = $request->employee_deduction_data ?? [];

                // Obtener datos del establecimiento
                $establishment = $blockPayroll->establishment;

                Log::info("Establishment obtenido: " . json_encode($establishment));

                foreach ($selectedWorkers as $index => $workerId) {
                    try {
                        // Obtener el worker
                        $worker = Worker::findOrFail($workerId);

                        // Obtener datos específicos del empleado
                        $periodData = $employeePeriodData[$workerId] ?? [];
                        $paymentData = $employeePaymentData[$workerId] ?? [];
                        $accruedData = $employeeAccruedData[$workerId] ?? [];
                        $deductionData = $employeeDeductionData[$workerId] ?? [];

                        // Construir JSON para este empleado
                        $employeeJson = $this->buildEmployeePayrollJson(
                            $worker,
                            $establishment,
                            $periodData,
                            $paymentData,
                            $accruedData,
                            $deductionData,
                            $request,
                            $blockPayroll
                        );

                        $employeeJsons[$workerId] = $employeeJson;

                        // Crear documento de nómina usando DocumentPayrollHelper
                        $documentResult = $this->createIndividualPayrollDocument($employeeJson, $worker);

                        if ($documentResult['success']) {
                            $generatedDocuments[] = $documentResult['document_id'];

                            // Procesar respuesta de la API para obtener validación
                            $responseValidation = $this->processApiResponse(
                                $documentResult['document'],
                                $workerId
                            );

                            $apiResponses[$workerId] = $responseValidation;
                        } else {
                            $errors[] = "Error generando documento para {$worker->fullname}: " . $documentResult['message'];

                            // Guardar error en respuestas
                            $apiResponses[$workerId] = [
                                'worker_id' => $workerId,
                                'worker_name' => $worker->fullname,
                                'document_id' => null,
                                'is_valid' => false,
                                'error' => $documentResult['message'],
                                'processed_at' => now()->toDateTimeString()
                            ];
                        }

                        $processedCount++;

                        // Log del progreso para debug
                        Log::info("Procesado empleado {$processedCount}/{$totalWorkers}: {$worker->fullname}");

                    } catch (\Exception $e) {
                        $errors[] = "Error procesando empleado ID {$workerId}: " . $e->getMessage();
                        $processedCount++;
                    }
                }

                // Determinar el estado del bloque basado en las validaciones individuales
                $allValid = true;
                $hasProcessingErrors = count($errors) > 0;
                $validCount = 0;
                $invalidCount = 0;
                $pendingCount = 0;

                // Analizar las respuestas de validación
                foreach ($apiResponses as $response) {
                    if (isset($response['is_valid'])) {
                        if ($response['is_valid'] === true) {
                            $validCount++;
                        } elseif ($response['is_valid'] === false) {
                            $invalidCount++;
                            $allValid = false;
                        } else {
                            $pendingCount++;
                            $allValid = false;
                        }
                    } else {
                        $pendingCount++;
                        $allValid = false;
                    }
                }

                // Determinar estado del bloque:
                // 5 = Aceptado: Solo si NO hay errores de procesamiento Y TODAS las validaciones son válidas
                // 1 = Registrado: Si hay errores de procesamiento O alguna validación es inválida/pendiente
                $blockState = ($hasProcessingErrors || !$allValid) ? 1 : 5;

                Log::info("Determinando estado del bloque:");
                Log::info("- Errores de procesamiento: " . ($hasProcessingErrors ? 'SÍ' : 'NO'));
                Log::info("- Todas las validaciones válidas: " . ($allValid ? 'SÍ' : 'NO'));
                Log::info("- Válidos: {$validCount}, Inválidos: {$invalidCount}, Pendientes: {$pendingCount}");
                Log::info("- Estado resultante: " . ($blockState === 5 ? 'Aceptado' : 'Registrado'));

                // Actualizar el bloque con los JSONs generados, respuestas y cambiar estado
                $blockPayroll->update([
                    'block_payroll_json' => $employeeJsons,
                    'block_payroll_json_responses' => $apiResponses,
                    'state_block_id' => $blockState,
                ]);

                return [
                    'block_payroll_id' => $blockPayroll->id,
                    'generated_documents' => $generatedDocuments,
                    'total_workers' => $totalWorkers,
                    'successful_documents' => count($generatedDocuments),
                    'valid_documents' => $validCount,
                    'invalid_documents' => $invalidCount,
                    'pending_documents' => $pendingCount,
                    'errors' => $errors,
                    'processed_count' => $processedCount,
                    'all_valid' => $allValid,
                    'block_state' => $blockState === 5 ? 'Aceptado' : 'Registrado'
                ];
            });

            // Crear mensaje descriptivo basado en los resultados
            $message = '';
            if (count($errors) > 0) {
                $message = "Bloque guardado con {$data['successful_documents']} documentos generados y " . count($errors) . " errores de procesamiento.";
            } elseif (!$data['all_valid']) {
                $message = "Bloque guardado. Documentos: {$data['valid_documents']} válidos, {$data['invalid_documents']} inválidos, {$data['pending_documents']} pendientes.";
            } else {
                $message = "Bloque guardado y ACEPTADO. Todos los {$data['valid_documents']} documentos fueron procesados y validados exitosamente.";
            }

            $message .= " Estado: {$data['block_state']}.";

            return [
                'success' => true,
                'message' => $message,
                'data' => $data,
                'errors' => $errors,
                'progress' => [
                    'current' => $processedCount,
                    'total' => $totalWorkers,
                    'percentage' => round(($processedCount / $totalWorkers) * 100, 2)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al guardar y generar el bloque de nómina: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Recalcular y actualizar el estado de un bloque basado en las validaciones individuales
     */
    public function recalculateBlockState($blockId)
    {
        try {
            $blockPayroll = BlockPayroll::findOrFail($blockId);
            $responses = $blockPayroll->block_payroll_json_responses;

            if (!$responses || empty($responses)) {
                return [
                    'success' => false,
                    'message' => 'No hay respuestas de validación para analizar'
                ];
            }

            $validCount = 0;
            $invalidCount = 0;
            $pendingCount = 0;
            $allValid = true;

            // Analizar las respuestas de validación
            foreach ($responses as $response) {
                if (isset($response['is_valid'])) {
                    if ($response['is_valid'] === true) {
                        $validCount++;
                    } elseif ($response['is_valid'] === false) {
                        $invalidCount++;
                        $allValid = false;
                    } else {
                        $pendingCount++;
                        $allValid = false;
                    }
                } else {
                    $pendingCount++;
                    $allValid = false;
                }
            }

            // Determinar nuevo estado: Solo Aceptado si TODAS las validaciones son válidas
            $newState = $allValid ? 5 : 1;
            $currentState = $blockPayroll->state_block_id;

            // Actualizar solo si hay cambio de estado
            if ($currentState !== $newState) {
                $blockPayroll->update(['state_block_id' => $newState]);

                Log::info("Estado del bloque {$blockId} actualizado de {$currentState} a {$newState}");

                return [
                    'success' => true,
                    'message' => 'Estado del bloque actualizado exitosamente',
                    'data' => [
                        'previous_state' => $currentState,
                        'new_state' => $newState,
                        'state_name' => $newState === 5 ? 'Aceptado' : 'Registrado',
                        'valid_count' => $validCount,
                        'invalid_count' => $invalidCount,
                        'pending_count' => $pendingCount,
                        'all_valid' => $allValid
                    ]
                ];
            } else {
                return [
                    'success' => true,
                    'message' => 'El estado del bloque no requiere actualización',
                    'data' => [
                        'current_state' => $currentState,
                        'state_name' => $currentState === 5 ? 'Aceptado' : 'Registrado',
                        'valid_count' => $validCount,
                        'invalid_count' => $invalidCount,
                        'pending_count' => $pendingCount,
                        'all_valid' => $allValid
                    ]
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al recalcular el estado del bloque: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Construir JSON individual para un empleado
     */
    private function buildEmployeePayrollJson($worker, $establishment, $periodData, $paymentData, $accruedData, $deductionData, $request, $blockPayroll)
    {
        // Obtener resolución seleccionada
        $resolution = \Modules\Factcolombia1\Models\Tenant\TypeDocument::find($request->resolution_id);

        Log::info("Resolution encontrada: " . json_encode($resolution));
        Log::info("Deduction data para worker {$worker->id}: " . json_encode($deductionData));

        // Verificar que la resolución exista
        if (!$resolution) {
            Log::error("No se encontró resolución con ID: " . $request->resolution_id);
            throw new \Exception("No se encontró la resolución especificada");
        }

        return [
            "type_document_id" => $request->resolution_id, // Usar resolution_id directamente
            "resolution_number" => $resolution->resolution_number ?? $resolution->name ?? '',
            "establishment_name" => $establishment->description ?? 'ESTABLECIMIENTO',
            "establishment_address" => $establishment->address ?? '',
            "establishment_phone" => $establishment->telephone ?? '',
            "establishment_municipality" => $establishment->department_id ?? 600,
            "establishment_email" => $establishment->email ?? '',
            "head_note" => $request->head_note ?? '',
            "foot_note" => $request->foot_note ?? '',
            "novelty" => [
                "novelty" => false,
                "uuidnov" => ""
            ],
            "period" => [
                "admision_date" => $this->formatDateOnly($periodData['admision_date'] ?? $worker->admision_date ?? date('Y-m-d')),
                "settlement_start_date" => $this->formatDateOnly($request->general_period_start),
                "settlement_end_date" => $this->formatDateOnly($request->general_period_end),
                "worked_time" => $periodData['worked_time'] ?? 30,
                "issue_date" => $this->formatDateOnly($request->date_of_issue)
            ],
            "worker_code" => $worker->code,
            "prefix" => $resolution->prefix ?? 'NI',
            "consecutive" => 0, // Se calculará en el backend
            "payroll_period_id" => $periodData['payroll_period'] ?? 5,
            "notes" => $request->notes ?? '',
            "worker" => [
                "type_worker_id" => $worker->type_worker_id ?? 1,
                "sub_type_worker_id" => $worker->sub_type_worker_id ?? 1,
                "payroll_type_document_identification_id" => $worker->payroll_type_document_identification_id ?? 1,
                "municipality_id" => $worker->municipality_id ?? 822,
                "type_contract_id" => $worker->type_contract_id ?? 1,
                "high_risk_pension" => $worker->high_risk_pension ?? false,
                "identification_number" => $worker->identification_number,
                "surname" => $worker->surname ?? '',
                "second_surname" => $worker->second_surname ?? '',
                "first_name" => $worker->first_name ?? '',
                "address" => $worker->address ?? '',
                "integral_salarary" => $worker->integral_salarary ?? false,
                "salary" => number_format($worker->salary, 2, '.', '')
            ],
            "payment" => [
                "payment_method_id" => $paymentData['payment_method_id'] ?? 10,
                "bank_name" => $paymentData['bank_name'] ?? '',
                "account_type" => $paymentData['account_type'] ?? '',
                "account_number" => $paymentData['account_number'] ?? ''
            ],
            "payment_dates" => $this->formatPaymentDates($paymentData['payment_dates'] ?? []),
            "accrued" => $this->formatAccruedData($accruedData),
            "deductions" => $this->formatDeductionData($deductionData)
        ];
    }

    /**
     * Formatear fechas de pago
     */
    private function formatPaymentDates($paymentDates)
    {
        $formatted = [];
        foreach ($paymentDates as $date) {
            $paymentDate = is_string($date) ? $date : ($date['payment_date'] ?? date('Y-m-d'));
            $formatted[] = [
                "payment_date" => $this->formatDateOnly($paymentDate)
            ];
        }

        // Si no hay fechas, agregar una por defecto
        if (empty($formatted)) {
            $formatted[] = ["payment_date" => $this->formatDateOnly(date('Y-m-d'))];
        }

        return $formatted;
    }

    /**
     * Formatear datos de devengados
     */
    private function formatAccruedData($accruedData)
    {
        return [
            "worked_days" => $accruedData['worked_days'] ?? 30,
            "salary" => number_format($accruedData['salary'] ?? 0, 2, '.', ''),
            "transportation_allowance" => number_format($accruedData['transportation_allowance'] ?? 0, 2, '.', ''),
            "HEDs" => $this->formatExtraHours($accruedData['heds'] ?? []),
            "HENs" => $this->formatExtraHours($accruedData['hens'] ?? []),
            "HRNs" => $this->formatExtraHours($accruedData['hrns'] ?? []),
            "HEDDFs" => $this->formatExtraHours($accruedData['heddfs'] ?? []),
            "HRDDFs" => $this->formatExtraHours($accruedData['hrddfs'] ?? []),
            "HENDFs" => $this->formatExtraHours($accruedData['hendfs'] ?? []),
            "HRNDFs" => $this->formatExtraHours($accruedData['hrndfs'] ?? []),
            "common_vacation" => $this->formatVacations($accruedData['common_vacation'] ?? []),
            "paid_vacation" => $this->formatPaidVacations($accruedData['paid_vacation'] ?? []),
            "service_bonus" => $this->formatServiceBonus($accruedData['service_bonus'] ?? []),
            "severance" => $this->formatSeverance($accruedData['severance'] ?? []),
            "work_disabilities" => $this->formatWorkDisabilities($accruedData['work_disabilities'] ?? []),
            "bonuses" => $this->formatBonuses($accruedData['bonuses'] ?? []),
            "aid" => $this->formatAid($accruedData['aid'] ?? []),
            "endowment" => number_format($accruedData['endowment'] ?? 0, 2, '.', ''),
            "sustenance_support" => number_format($accruedData['sustenance_support'] ?? 0, 2, '.', ''),
            "telecommuting" => number_format($accruedData['telecommuting'] ?? 0, 2, '.', ''),
            "withdrawal_bonus" => number_format($accruedData['withdrawal_bonus'] ?? 0, 2, '.', ''),
            "compensation" => number_format($accruedData['compensation'] ?? 0, 2, '.', ''),
            "accrued_total" => number_format($accruedData['accrued_total'] ?? 0, 2, '.', '')
        ];
    }

    /**
     * Formatear datos de deducciones
     */
    private function formatDeductionData($deductionData)
    {
        // Si no hay datos de deducción o está vacío, usar valores por defecto
        if (empty($deductionData)) {
            return [
                "eps_type_law_deductions_id" => 1,
                "eps_deduction" => "0.00",
                "pension_type_law_deductions_id" => 1,
                "pension_deduction" => "0.00",
                "fondossp_type_law_deductions_id" => 1,
                "fondosp_deduction_SP" => "0.00",
                "fondossp_sub_type_law_deductions_id" => 1,
                "fondosp_deduction_sub" => "0.00",
                "labor_union" => [],
                "sanctions" => [],
                "voluntary_pension" => "0.00",
                "withholding_at_source" => "0.00",
                "afc" => "0.00",
                "cooperative" => "0.00",
                "tax_liens" => "0.00",
                "supplementary_plan" => "0.00",
                "education" => "0.00",
                "refund" => "0.00",
                "debt" => "0.00",
                "deductions_total" => "0.00"
            ];
        }

        return [
            "eps_type_law_deductions_id" => $deductionData['eps_type_law_deductions_id'] ?? 1,
            "eps_deduction" => number_format($deductionData['eps_deduction'] ?? 0, 2, '.', ''),
            "pension_type_law_deductions_id" => $deductionData['pension_type_law_deductions_id'] ?? 1,
            "pension_deduction" => number_format($deductionData['pension_deduction'] ?? 0, 2, '.', ''),
            "fondossp_type_law_deductions_id" => $deductionData['fondossp_type_law_deductions_id'] ?? 1,
            "fondosp_deduction_SP" => number_format($deductionData['fondosp_deduction_SP'] ?? 0, 2, '.', ''),
            "fondossp_sub_type_law_deductions_id" => $deductionData['fondossp_sub_type_law_deductions_id'] ?? 1,
            "fondosp_deduction_sub" => number_format($deductionData['fondosp_deduction_sub'] ?? 0, 2, '.', ''),
            "labor_union" => $this->formatLaborUnion($deductionData['labor_union'] ?? []),
            "sanctions" => $this->formatSanctions($deductionData['sanctions'] ?? []),
            "voluntary_pension" => number_format($deductionData['voluntary_pension'] ?? 0, 2, '.', ''),
            "withholding_at_source" => number_format($deductionData['withholding_at_source'] ?? 0, 2, '.', ''),
            "afc" => number_format($deductionData['afc'] ?? 0, 2, '.', ''),
            "cooperative" => number_format($deductionData['cooperative'] ?? 0, 2, '.', ''),
            "tax_liens" => number_format($deductionData['tax_liens'] ?? 0, 2, '.', ''),
            "supplementary_plan" => number_format($deductionData['supplementary_plan'] ?? 0, 2, '.', ''),
            "education" => number_format($deductionData['education'] ?? 0, 2, '.', ''),
            "refund" => number_format($deductionData['refund'] ?? 0, 2, '.', ''),
            "debt" => number_format($deductionData['debt'] ?? 0, 2, '.', ''),
            "deductions_total" => number_format($deductionData['deductions_total'] ?? 0, 2, '.', '')
        ];
    }

    // Métodos auxiliares de formateo
    private function formatExtraHours($hours) {
        $formatted = [];
        foreach ($hours as $hour) {
            $formatted[] = [
                "start_time" => $hour['start_time'] ?? '',
                "end_time" => $hour['end_time'] ?? '',
                "quantity" => $hour['quantity'] ?? 0,
                "percentage" => $hour['percentage'] ?? 0,
                "payment" => number_format($hour['payment'] ?? 0, 2, '.', '')
            ];
        }
        return $formatted;
    }

    private function formatVacations($vacations) {
        $formatted = [];
        foreach ($vacations as $vacation) {
            $formatted[] = [
                "start_date" => $vacation['start_date'] ?? '',
                "end_date" => $vacation['end_date'] ?? '',
                "quantity" => $vacation['quantity'] ?? 0,
                "payment" => number_format($vacation['payment'] ?? 0, 2, '.', '')
            ];
        }
        return $formatted;
    }

    private function formatPaidVacations($vacations) {
        $formatted = [];
        foreach ($vacations as $vacation) {
            $formatted[] = [
                "quantity" => round($vacation['quantity'] ?? 0),
                "payment" => number_format($vacation['payment'] ?? 0, 2, '.', '')
            ];
        }
        return $formatted;
    }

    private function formatServiceBonus($bonuses) {
        $formatted = [];
        foreach ($bonuses as $bonus) {
            $formatted[] = [
                "quantity" => round($bonus['quantity'] ?? 0),
                "payment" => number_format($bonus['payment'] ?? 0, 2, '.', ''),
                "paymentNS" => number_format($bonus['paymentNS'] ?? 0, 2, '.', ''),
                "is_automatic_provision" => $bonus['is_automatic_provision'] ?? true
            ];
        }
        return $formatted;
    }

    private function formatSeverance($severances) {
        $formatted = [];
        foreach ($severances as $severance) {
            $formatted[] = [
                "payment" => number_format($severance['payment'] ?? 0, 2, '.', ''),
                "quantity" => round($severance['quantity'] ?? 0),
                "percentage" => $severance['percentage'] ?? "12",
                "interest_payment" => number_format($severance['interest_payment'] ?? 0, 2, '.', ''),
                "is_automatic_provision" => $severance['is_automatic_provision'] ?? true
            ];
        }
        return $formatted;
    }

    private function formatWorkDisabilities($disabilities) {
        $formatted = [];
        foreach ($disabilities as $disability) {
            $formatted[] = [
                "start_date" => $disability['start_date'] ?? '',
                "end_date" => $disability['end_date'] ?? '',
                "type" => $disability['type'] ?? 3,
                "quantity" => $disability['quantity'] ?? 0,
                "payment" => number_format($disability['payment'] ?? 0, 2, '.', '')
            ];
        }
        return $formatted;
    }

    private function formatBonuses($bonuses) {
        $formatted = [];
        foreach ($bonuses as $bonus) {
            $formatted[] = [
                "salary_bonus" => number_format($bonus['salary_bonus'] ?? 0, 2, '.', ''),
                "non_salary_bonus" => number_format($bonus['non_salary_bonus'] ?? 0, 2, '.', '')
            ];
        }
        return $formatted;
    }

    private function formatAid($aids) {
        $formatted = [];
        foreach ($aids as $aid) {
            $formatted[] = [
                "salary_assistance" => number_format($aid['salary_assistance'] ?? 0, 2, '.', ''),
                "non_salary_assistance" => number_format($aid['non_salary_assistance'] ?? 0, 2, '.', '')
            ];
        }
        return $formatted;
    }

    private function formatLaborUnion($unions) {
        $formatted = [];
        foreach ($unions as $union) {
            $formatted[] = [
                "percentage" => number_format($union['percentage'] ?? 0, 2, '.', ''),
                "deduction" => number_format($union['deduction'] ?? 0, 2, '.', '')
            ];
        }
        return $formatted;
    }

    private function formatSanctions($sanctions) {
        $formatted = [];
        foreach ($sanctions as $sanction) {
            $formatted[] = [
                "public_sanction" => number_format($sanction['public_sanction'] ?? 0, 2, '.', ''),
                "private_sanction" => number_format($sanction['private_sanction'] ?? 0, 2, '.', '')
            ];
        }
        return $formatted;
    }

    /**
     * Crear documento individual de nómina usando DocumentPayrollHelper
     */
    private function createIndividualPayrollDocument($employeeJson, $worker)
    {
        try {
            // Crear una instancia de Request con los datos estructurados correctamente
            $requestData = [
                'type_document_id' => $employeeJson['type_document_id'],
                'worker_id' => $worker->id,
                'prefix' => $employeeJson['prefix'],
                'consecutive' => $employeeJson['consecutive'],
                'payroll_period_id' => $employeeJson['payroll_period_id'],
                'notes' => $employeeJson['notes'],
                'head_note' => $employeeJson['head_note'],
                'foot_note' => $employeeJson['foot_note'],
                'resolution_number' => $employeeJson['resolution_number'],
                'period' => $employeeJson['period'],
                'payment' => $employeeJson['payment'],
                'payment_dates' => $employeeJson['payment_dates'],
                'accrued' => $employeeJson['accrued'],
                'deductions' => $employeeJson['deductions'],
                'novelty' => $employeeJson['novelty']
            ];

            $request = new Request($requestData);

            // Usar DocumentPayrollHelper para crear el documento
            $helper = new DocumentPayrollHelper();
            $inputs = $helper->getInputs($request);

            // Sobrescribir algunos campos con los datos específicos del JSON
            $inputs['period'] = $employeeJson['period'];
            $inputs['payment'] = $employeeJson['payment'];
            $inputs['payment_dates'] = $employeeJson['payment_dates'];
            $inputs['notes'] = $employeeJson['notes'];
            $inputs['head_note'] = $employeeJson['head_note'];
            $inputs['foot_note'] = $employeeJson['foot_note'];
            $inputs['novelty'] = $employeeJson['novelty'];

            // Crear documento en base de datos
            $document = DocumentPayroll::create($inputs);
            $document->accrued()->create($inputs['accrued']);
            $document->deduction()->create($inputs['deductions']);

            // Enviar a la API
            $response = $helper->sendToApi($document, $inputs);

            $document->update([
                'response_api' => $response
            ]);

            // Recargar el documento para obtener la respuesta actualizada
            $document->refresh();

            return [
                'success' => true,
                'document_id' => $document->id,
                'document' => $document
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Formatear fecha para asegurar formato Y-m-d
     */
    private function formatDateOnly($date)
    {
        if (empty($date)) {
            return date('Y-m-d');
        }

        // Si la fecha ya está en formato Y-m-d, devolverla tal como está
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Intentar parsear la fecha y convertirla a Y-m-d
        try {
            $dateTime = new \DateTime($date);
            return $dateTime->format('Y-m-d');
        } catch (\Exception $e) {
            // Si falla el parseo, devolver fecha actual
            return date('Y-m-d');
        }
    }

    /**
     * Procesar respuesta de la API para determinar validez del documento
     */
    private function processApiResponse($document, $workerId)
    {
        try {
            $worker = Worker::find($workerId);
            $response = $document->response_api;

            Log::info("Procesando respuesta API para worker {$workerId}");
            Log::info("Response API: " . json_encode($response));

            $responseData = [
                'worker_id' => $workerId,
                'worker_name' => $worker ? $worker->fullname : "Worker #{$workerId}",
                'document_id' => $document->id,
                'is_valid' => false,
                'original_api_response' => $response, // Siempre guardar la respuesta original
                'processed_at' => now()->toDateTimeString()
            ];

            // Convertir a array si es objeto para facilitar la búsqueda
            if (is_object($response)) {
                $response = json_decode(json_encode($response), true);
            }

            // Caso 1: La respuesta contiene directamente isValid
            $isValidFound = $this->searchIsValidInResponse($response);
            if ($isValidFound !== null) {
                $responseData['is_valid'] = $isValidFound;
                $responseData['validation_method'] = 'direct_isValid';
                Log::info("IsValid encontrado directamente: " . ($isValidFound ? 'true' : 'false'));
                return $responseData;
            }

            // Caso 2: La respuesta contiene ZipKey, necesita consulta adicional
            $zipKey = $this->searchZipKeyInResponse($response);
            if ($zipKey) {
                Log::info("ZipKey encontrado: {$zipKey}");
                $responseData['zip_key'] = $zipKey;

                // Realizar consulta al método queryZipkey
                $zipKeyValidation = $this->queryZipKeyValidation($document->id, $zipKey);

                if ($zipKeyValidation && isset($zipKeyValidation['success']) && $zipKeyValidation['success']) {
                    // Guardar también la respuesta de la consulta ZipKey
                    $responseData['zipkey_query_response'] = $zipKeyValidation;

                    $zipKeyIsValid = $this->searchIsValidInResponse($zipKeyValidation);
                    if ($zipKeyIsValid !== null) {
                        $responseData['is_valid'] = $zipKeyIsValid;
                        $responseData['validation_method'] = 'zipkey_query_isvalid';
                        Log::info("IsValid de ZipKey encontrado: " . ($zipKeyIsValid ? 'true' : 'false'));
                        return $responseData;
                    } else {
                        // Si no se encuentra isValid, analizar el mensaje para determinar autorización
                        $isValidFromMessage = $this->analyzeAuthorizationMessage($zipKeyValidation, $document);
                        $responseData['is_valid'] = $isValidFromMessage;
                        $responseData['validation_method'] = 'zipkey_query_message';

                        if ($isValidFromMessage) {
                            Log::info("Nómina autorizada según mensaje para worker {$workerId}");
                        } else {
                            Log::info("Nómina NO autorizada según mensaje para worker {$workerId}");
                        }

                        return $responseData;
                    }
                } else {
                    $responseData['error'] = 'Error al consultar ZipKey';
                    $responseData['validation_method'] = 'zipkey_query_error';
                    $responseData['zipkey_query_response'] = $zipKeyValidation;
                    Log::error("Error consultando ZipKey para worker {$workerId}: " . json_encode($zipKeyValidation));
                    return $responseData;
                }
            }

            // Caso 3: No se encontró ni isValid ni ZipKey
            $responseData['error'] = 'No se encontró isValid ni ZipKey en la respuesta';
            $responseData['validation_method'] = 'no_validation_found';
            Log::warning("No se encontró validación para worker {$workerId}");
            Log::warning("Respuesta completa analizada: " . json_encode($response));
            return $responseData;

        } catch (\Exception $e) {
            Log::error("Error procesando respuesta API para worker {$workerId}: " . $e->getMessage());
            return [
                'worker_id' => $workerId,
                'worker_name' => $worker ? $worker->fullname : "Worker #{$workerId}",
                'document_id' => $document->id,
                'is_valid' => false,
                'original_api_response' => $document->response_api ?? null,
                'error' => 'Error procesando respuesta: ' . $e->getMessage(),
                'validation_method' => 'processing_error',
                'processed_at' => now()->toDateTimeString()
            ];
        }
    }

    /**
     * Buscar el campo isValid en cualquier nivel de la respuesta
     */
    private function searchIsValidInResponse($response, $maxDepth = 15, $currentDepth = 0, $path = '')
    {
        if ($currentDepth >= $maxDepth) {
            return null;
        }

        if (is_array($response) || is_object($response)) {
            foreach ($response as $key => $value) {
                $currentPath = $path ? $path . '.' . $key : $key;

                // Verificar múltiples variaciones de isValid
                $keyLower = strtolower($key);
                if (in_array($keyLower, ['isvalid', 'is_valid', 'valid', 'esvalido', 'valido'])) {
                    Log::info("Campo isValid encontrado en ruta: {$currentPath} con valor: " . json_encode($value));
                    return (bool) $value;
                }

                // Búsqueda recursiva en objetos/arrays anidados
                if (is_array($value) || is_object($value)) {
                    $result = $this->searchIsValidInResponse($value, $maxDepth, $currentDepth + 1, $currentPath);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Buscar el campo ZipKey en cualquier nivel de la respuesta
     */
    private function searchZipKeyInResponse($response, $maxDepth = 15, $currentDepth = 0, $path = '')
    {
        if ($currentDepth >= $maxDepth) {
            return null;
        }

        if (is_array($response) || is_object($response)) {
            foreach ($response as $key => $value) {
                $currentPath = $path ? $path . '.' . $key : $key;

                // Verificar múltiples variaciones de ZipKey
                $keyLower = strtolower($key);
                if (in_array($keyLower, ['zipkey', 'zip_key', 'zipcode', 'zip'])) {
                    Log::info("Campo ZipKey encontrado en ruta: {$currentPath} con valor: " . json_encode($value));
                    return $value;
                }

                // Búsqueda recursiva en objetos/arrays anidados
                if (is_array($value) || is_object($value)) {
                    $result = $this->searchZipKeyInResponse($value, $maxDepth, $currentDepth + 1, $currentPath);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Realizar consulta de validación usando ZipKey
     */
    private function queryZipKeyValidation($documentId, $zipKey)
    {
        try {
            // Pausa de 2 segundos para permitir que la DIAN procese el documento
            // antes de consultar el ZipKey, ya que puede haber delay en el procesamiento
            sleep(2);

            // Crear una instancia del controlador de documentos
            $documentController = app(\Modules\Payroll\Http\Controllers\DocumentPayrollController::class);

            // Crear un request simulado para el método queryZipkey
            $request = new Request(['id' => $documentId]);

            // Llamar al método queryZipkey
            $result = $documentController->queryZipkey($request);

            Log::info("Resultado de queryZipkey para documento {$documentId}: " . json_encode($result));

            return $result;

        } catch (\Exception $e) {
            Log::error("Error en queryZipKeyValidation: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analizar mensaje de autorización para determinar si la nómina fue autorizada
     */
    private function analyzeAuthorizationMessage($zipKeyResponse, $document)
    {
        try {
            // Buscar el campo message en la respuesta
            $message = $this->searchFieldInResponse($zipKeyResponse, ['message', 'mensaje', 'msg']);

            if (!$message) {
                Log::warning("No se encontró mensaje en la respuesta ZipKey para documento {$document->id}");
                return false;
            }

            Log::info("Analizando mensaje de autorización: {$message}");

            // Obtener información del documento para construir el patrón esperado
            $documentPayroll = DocumentPayroll::find($document->id);
            if (!$documentPayroll) {
                Log::error("No se encontró el documento de nómina {$document->id}");
                return false;
            }

            // Construir el patrón de mensaje de autorización
            $prefix = $documentPayroll->prefix ?? 'NI';
            $consecutive = $documentPayroll->consecutive;

            // Patrones de mensajes de autorización exitosa
            $authorizationPatterns = [
                "/00\s*-\s*Procesado\s+Correctamente\.\s*-\s*La\s+Nomina\s+Individual\s+{$prefix}-{$consecutive},\s+ha\s+sido\s+autorizada\./i",
                "/00\s*-\s*Procesado\s+Correctamente\.\s*-\s*La\s+Nómina\s+Individual\s+{$prefix}-{$consecutive},\s+ha\s+sido\s+autorizada\./i",
                "/Procesado\s+Correctamente.*{$prefix}-{$consecutive}.*autorizada/i",
                "/autorizada.*{$prefix}-{$consecutive}/i",
                "/00.*Procesado.*Correctamente/i"
            ];

            // Verificar si el mensaje coincide con algún patrón de autorización
            foreach ($authorizationPatterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    Log::info("Mensaje de autorización coincide con patrón: {$pattern}");
                    return true;
                }
            }

            // Verificar patrones de error comunes
            $errorPatterns = [
                "/error/i",
                "/rechazada/i",
                "/no\s+autorizada/i",
                "/fallida/i",
                "/inválida/i",
                "/no\s+válida/i"
            ];

            foreach ($errorPatterns as $pattern) {
                if (preg_match($pattern, $message)) {
                    Log::info("Mensaje indica error/rechazo con patrón: {$pattern}");
                    return false;
                }
            }

            // Si no coincide con ningún patrón conocido, loggear para análisis
            Log::warning("Mensaje no coincide con patrones conocidos: {$message}");
            return false;

        } catch (\Exception $e) {
            Log::error("Error analizando mensaje de autorización: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar un campo específico en cualquier nivel de la respuesta
     */
    private function searchFieldInResponse($response, $fieldNames, $maxDepth = 10, $currentDepth = 0)
    {
        if ($currentDepth >= $maxDepth) {
            return null;
        }

        if (is_array($response) || is_object($response)) {
            foreach ($response as $key => $value) {
                // Verificar si la clave actual coincide con algún nombre de campo
                $keyLower = strtolower($key);
                foreach ($fieldNames as $fieldName) {
                    if ($keyLower === strtolower($fieldName)) {
                        return $value;
                    }
                }

                // Búsqueda recursiva en objetos/arrays anidados
                if (is_array($value) || is_object($value)) {
                    $result = $this->searchFieldInResponse($value, $fieldNames, $maxDepth, $currentDepth + 1);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Crear PDF del bloque de nómina
     */
    private function createPayrollPDF($blockPayroll, $employeesData, $showWatermark = false)
    {
        $html = $this->generatePayrollHTML($blockPayroll, $employeesData, $showWatermark);
        $pdf = PDF::loadHTML($html)->setWarnings(false);
        $pdf->setPaper('A4', 'landscape');

        return $pdf;
    }

    /**
     * Generar HTML para el PDF del bloque de nómina
     */
    private function generatePayrollHTML($blockPayroll, $employeesData, $showWatermark = false)
    {
        // Obtener información de la empresa
        $company = Company::first();

        if (!$company) {
            // Si no hay empresa activa, crear objeto por defecto
            $company = (object) [
                'identification_number' => 'N/A',
                'trade_name' => 'EMPRESA',
                'name' => 'EMPRESA'
            ];
        }

        $logoPath = public_path("storage/uploads/logos/logo_{$company->identification_number}.jpg");
        $logoExists = file_exists($logoPath);
        $logoBase64 = '';

        if ($logoExists) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
        }

        // Obtener período del JSON del bloque
        $period = $blockPayroll->period;
        $periodStart = isset($period->period_start) ? $period->period_start : 'N/A';
        $periodEnd = isset($period->period_end) ? $period->period_end : 'N/A';

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Bloque de Nómina #' . $blockPayroll->id . '</title>
            <style>
                @page {
                    margin: 8mm;
                    size: A4 landscape;
                }

                body {
                    font-family: Arial, sans-serif;
                    font-size: 9px;
                    margin: 0;
                    padding: 0;
                    position: relative;
                }

                .watermark {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-45deg);
                    font-size: 60px;
                    color: rgba(255, 0, 0, 0.2);
                    z-index: -1;
                    font-weight: bold;
                }

                .watermark-multiple {
                    position: absolute;
                    font-size: 40px;
                    color: rgba(255, 0, 0, 0.15);
                    z-index: -1;
                    font-weight: bold;
                    transform: rotate(-45deg);
                }

                .watermark-1 {
                    top: 20%;
                    left: 20%;
                }

                .watermark-2 {
                    top: 20%;
                    right: 20%;
                }

                .watermark-3 {
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-45deg);
                }

                .watermark-4 {
                    bottom: 20%;
                    left: 20%;
                }

                .watermark-5 {
                    bottom: 20%;
                    right: 20%;
                }

                .header {
                    margin-bottom: 15px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                    position: relative;
                    min-height: 60px;
                }

                .logo {
                    position: absolute;
                    top: 0;
                    left: 0;
                    max-height: 50px;
                    max-width: 150px;
                }

                .company-info {
                    text-align: center;
                    padding-top: 5px;
                }

                .company-info h1 {
                    margin: 0;
                    color: #333;
                    font-size: 14px;
                    font-weight: bold;
                }

                .company-info h2 {
                    margin: 2px 0;
                    color: #666;
                    font-size: 12px;
                    font-weight: normal;
                }

                .block-info {
                    width: 100%;
                    margin-bottom: 10px;
                    border-collapse: collapse;
                    font-size: 9px;
                }

                .block-info td {
                    padding: 4px 8px;
                    border: 1px solid #ddd;
                    background-color: #f9f9f9;
                }

                .block-info-label {
                    font-weight: bold;
                    color: #555;
                }

                .employees-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 8px;
                    margin-top: 10px;
                }

                .employees-table th {
                    background-color: #4CAF50;
                    color: white;
                    border: 1px solid #333;
                    padding: 4px 2px;
                    text-align: center;
                    font-weight: bold;
                    font-size: 7px;
                    vertical-align: middle;
                }

                .employees-table td {
                    border: 1px solid #ddd;
                    padding: 3px 2px;
                    font-size: 8px;
                    vertical-align: top;
                    text-align: center;
                }

                .employee-name {
                    text-align: left !important;
                    font-weight: bold;
                    max-width: 120px;
                    word-wrap: break-word;
                    font-size: 7px;
                }

                .currency {
                    text-align: right !important;
                    font-family: monospace;
                    font-size: 8px;
                    min-width: 60px;
                }

                .totals-section {
                    background-color: #fff3cd;
                    border: 2px solid #ffeaa7;
                    padding: 8px;
                    margin-top: 10px;
                    text-align: center;
                    font-size: 10px;
                }

                .total-item {
                    display: inline-block;
                    margin-right: 15px;
                    font-weight: bold;
                }

                .small-text {
                    font-size: 6px;
                }
            </style>
        </head>
        <body>';

        // Marcas de agua múltiples si el documento no está aceptado
        if ($showWatermark) {
            $html .= '
                <div class="watermark-multiple watermark-1">NO ENVIADO A DIAN</div>
                <div class="watermark-multiple watermark-2">NO ENVIADO A DIAN</div>
                <div class="watermark watermark-3">NO ENVIADO A DIAN</div>
                <div class="watermark-multiple watermark-4">NO ENVIADO A DIAN</div>
                <div class="watermark-multiple watermark-5">NO ENVIADO A DIAN</div>';
        }

        // Encabezado con logo
        $html .= '<div class="header">';

        if ($logoExists) {
            $html .= '<img src="' . $logoBase64 . '" class="logo" alt="Logo">';
        }

        $html .= '
            <div class="company-info">
                <h1>' . ($company->trade_name ?? $company->name ?? 'EMPRESA') . '</h1>
                <h2>NIT: ' . ($company->identification_number ?? 'N/A') . '</h2>
                <h2>BLOQUE DE NÓMINA #' . $blockPayroll->id . '</h2>
                <div class="small-text">Generado el ' . date('d/m/Y H:i:s') . '</div>
            </div>
        </div>';

        // Información del bloque
        $html .= '
            <table class="block-info">
                <tr>
                    <td>
                        <div class="block-info-label">Fecha de Emisión:</div>
                        <div>' . $blockPayroll->date_of_issue . '</div>
                    </td>
                    <td>
                        <div class="block-info-label">Estado:</div>
                        <div>' . ($blockPayroll->state_block->name ?? 'Sin estado') . '</div>
                    </td>
                    <td>
                        <div class="block-info-label">Empleados:</div>
                        <div>' . $blockPayroll->workers_quantity . '</div>
                    </td>
                    <td>
                        <div class="block-info-label">Período:</div>
                        <div>' . $periodStart . ' - ' . $periodEnd . '</div>
                    </td>
                </tr>
            </table>';

        // Tabla de empleados
        $html .= '<table class="employees-table">';

        // Encabezados de la tabla
        $html .= '
            <thead>
                <tr>
                    <th style="width: 13%;">EMPLEADO</th>
                    <th style="width: 7%;">IDENTIFICACIÓN</th>
                    <th style="width: 4%;">DÍAS<br>TRABAJADOS</th>
                    <th style="width: 8%;">SALARIO<br>BÁSICO</th>
                    <th style="width: 7%;">VACACIONES</th>
                    <th style="width: 7%;">PRIMA<br>SERVICIOS</th>
                    <th style="width: 7%;">CESANTÍAS</th>
                    <th style="width: 8%;">TOTAL<br>DEVENGADO</th>
                    <th style="width: 7%;">SALUD</th>
                    <th style="width: 7%;">PENSIÓN</th>
                    <th style="width: 5%;">OTRAS<br>DEDUCCIONES</th>
                    <th style="width: 8%;">TOTAL<br>DEDUCCIONES</th>
                    <th style="width: 8%;">NETO A<br>PAGAR</th>
                    <th style="width: 6%;">ESTADO</th>
                </tr>
            </thead>
            <tbody>';

        // Filas de empleados
        foreach ($employeesData as $workerId => $employee) {
            $html .= $this->generateEmployeeRowHTML($employee, $blockPayroll, $workerId);
        }

        $html .= '</tbody></table>';

        // Totales del bloque
        $html .= '
            <div class="totals-section">
                <div class="total-item">Total Devengados: $' . number_format($blockPayroll->accrued_total, 2, '.', ',') . '</div>
                <div class="total-item">Total Deducciones: $' . number_format($blockPayroll->deductions_total, 2, '.', ',') . '</div>
                <div class="total-item">Valor Neto: $' . number_format($blockPayroll->accrued_total - $blockPayroll->deductions_total, 2, '.', ',') . '</div>
            </div>';

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Generar fila HTML para un empleado en la tabla
     */
    private function generateEmployeeRowHTML($employee, $blockPayroll = null, $workerId = null)
    {
        // Extraer datos del empleado - nombre completo
        $firstName = $employee['worker']['first_name'] ?? '';
        $surname = $employee['worker']['surname'] ?? '';
        $secondSurname = $employee['worker']['second_surname'] ?? '';
        $workerName = trim($firstName . ' ' . $surname . ' ' . $secondSurname);
        $identification = $employee['worker']['identification_number'] ?? 'N/A';

        // Usar directamente los datos del JSON payload para devengados
        $workedDays = $employee['accrued']['worked_days'] ?? 0;
        $salaryPayment = floatval($employee['accrued']['salary'] ?? 0);

        // Usar los totales calculados del JSON payload
        $totalAccrued = floatval($employee['accrued']['accrued_total'] ?? 0);
        $totalDeductions = floatval($employee['deductions']['deductions_total'] ?? 0);

        // Vacaciones - usar datos del JSON
        $vacationPayment = 0;
        if (!empty($employee['accrued']['paid_vacation'])) {
            foreach ($employee['accrued']['paid_vacation'] as $vacation) {
                $vacationPayment += floatval($vacation['payment'] ?? 0);
            }
        }

        // Prima de servicios - usar datos del JSON
        $bonusPayment = 0;
        if (!empty($employee['accrued']['service_bonus'])) {
            foreach ($employee['accrued']['service_bonus'] as $bonus) {
                $bonusPayment += floatval($bonus['payment'] ?? 0);
            }
        }

        // Cesantías - usar datos del JSON
        $severancePayment = 0;
        if (!empty($employee['accrued']['severance'])) {
            foreach ($employee['accrued']['severance'] as $severance) {
                $severancePayment += floatval($severance['payment'] ?? 0);
            }
        }

        // Deducciones específicas - usar datos del JSON
        $healthPayment = floatval($employee['deductions']['eps_deduction'] ?? 0);
        $pensionPayment = floatval($employee['deductions']['pension_deduction'] ?? 0);

        // Otras deducciones (suma de todas las demás)
        $otherDeductions = 0;
        $otherDeductions += floatval($employee['deductions']['fondosp_deduction_SP'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['fondosp_deduction_sub'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['voluntary_pension'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['withholding_at_source'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['afc'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['cooperative'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['tax_liens'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['supplementary_plan'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['education'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['refund'] ?? 0);
        $otherDeductions += floatval($employee['deductions']['debt'] ?? 0);

        // Neto a pagar
        $netPayment = $totalAccrued - $totalDeductions;

        // Determinar estado del trabajador
        $payrollStatus = 'Pendiente';
        $statusColor = '#ffecb3'; // Amarillo claro para pendiente

        if ($blockPayroll && $workerId && $blockPayroll->block_payroll_json_responses) {
            $jsonResponses = $blockPayroll->block_payroll_json_responses;
            if (isset($jsonResponses[$workerId])) {
                $workerResponse = $jsonResponses[$workerId];

                // Usar exactamente la misma lógica que el frontend: solo verificar is_valid
                if (isset($workerResponse['is_valid']) && $workerResponse['is_valid'] === true) {
                    $payrollStatus = 'Aceptada';
                    $statusColor = '#c8e6c9'; // Verde claro para aceptada
                }
            }
        }

        $html = '<tr>
            <td class="employee-name">' . htmlspecialchars($workerName) . '</td>
            <td>' . htmlspecialchars($identification) . '</td>
            <td>' . $workedDays . '</td>
            <td class="currency">$' . number_format($salaryPayment, 2, '.', ',') . '</td>
            <td class="currency">$' . number_format($vacationPayment, 2, '.', ',') . '</td>
            <td class="currency">$' . number_format($bonusPayment, 2, '.', ',') . '</td>
            <td class="currency">$' . number_format($severancePayment, 2, '.', ',') . '</td>
            <td class="currency" style="background-color: #e8f5e8; font-weight: bold;">$' . number_format($totalAccrued, 2, '.', ',') . '</td>
            <td class="currency">$' . number_format($healthPayment, 2, '.', ',') . '</td>
            <td class="currency">$' . number_format($pensionPayment, 2, '.', ',') . '</td>
            <td class="currency">$' . number_format($otherDeductions, 2, '.', ',') . '</td>
            <td class="currency" style="background-color: #ffe8e8; font-weight: bold;">$' . number_format($totalDeductions, 2, '.', ',') . '</td>
            <td class="currency" style="background-color: #e8e8ff; font-weight: bold;">$' . number_format($netPayment, 2, '.', ',') . '</td>
            <td style="background-color: ' . $statusColor . '; font-weight: bold; text-align: center;">' . $payrollStatus . '</td>
        </tr>';

        return $html;
    }

}
