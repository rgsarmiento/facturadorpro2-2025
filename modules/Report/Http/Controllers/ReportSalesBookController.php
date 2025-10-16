<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use Modules\Report\Traits\ReportSalesBookTrait;
use Modules\Report\Traits\PdfMemoryManagement;
use Modules\Report\Exports\SaleBookExport;


class ReportSalesBookController extends Controller
{

    use ReportSalesBookTrait;
    use PdfMemoryManagement;


    public function index()
    {
        return view('report::co-sales-book.index');
    }


    /**
     *
     * @param  string $type
     * @param  Request $request
     * @return mixed
     */
    public function export($type, Request $request)
    {
        try {
            // Configurar recursos para generación de PDFs (memoria y tiempo)
            $this->configurePdfResources('2G', 600);

            \Log::info('Iniciando generación de PDF - Libro de Ventas', [
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB'
            ]);

            $request['summary_sales_book'] = $request->summary_sales_book === 'true';
            $company = Company::first();
            $establishment = auth()->user()->establishment;
            $filters = $request;
            $data = $this->getData($request);
            $records = $data['records'];
            $taxes = $this->getTaxesDocuments($records);
            $summary_records = $request->summary_sales_book ? $this->getSummaryRecords($data, $request) : [];
            $report_data = compact('records', 'company', 'establishment', 'filters', 'taxes', 'summary_records');

            \Log::info('Datos cargados - Libro de Ventas', [
                'records_count' => count($records),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
                'memory_limit' => ini_get('memory_limit')
            ]);

            switch ($type) {
                case 'excel':
                    return (new SaleBookExport)
                        ->records($report_data)
                        ->download('Reporte_Libro_Ventas_'.date('YmdHis').'.xlsx');
                    break;
                default:
                    // Verificar si hay demasiados registros para PDF
                    $maxRecordsForPdf = 5000;
                    if (count($records) > $maxRecordsForPdf) {
                        \Log::warning('Demasiados registros para PDF - Libro de Ventas', [
                            'records_count' => count($records),
                            'max_allowed' => $maxRecordsForPdf
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => "El reporte tiene " . count($records) . " registros. Para reportes con más de {$maxRecordsForPdf} registros, por favor use la exportación a Excel.",
                            'records_count' => count($records),
                            'max_allowed' => $maxRecordsForPdf
                        ], 400);
                    }

                    // Forzar límite de memoria nuevamente antes de generar PDF
                    ini_set('memory_limit', '2G');

                    \Log::info('Generando PDF - Libro de Ventas', [
                        'memory_limit' => ini_get('memory_limit'),
                        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB'
                    ]);

                    $pdf = PDF::loadView('report::co-sales-book.report_pdf', $report_data)->setPaper('a4', 'landscape');
                    $filename = 'Reporte_Libro_Ventas_'.date('YmdHis');

                    \Log::info('PDF generado - Libro de Ventas', [
                        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB'
                    ]);

                    $this->freeMemory();
                    return $pdf->stream($filename.'.pdf');
                    break;
            }
        } catch (\Exception $e) {
            \Log::error('Error generando reporte Libro de Ventas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

}
