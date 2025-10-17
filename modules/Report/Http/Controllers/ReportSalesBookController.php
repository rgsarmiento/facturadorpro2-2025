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

            $request['summary_sales_book'] = $request->summary_sales_book === 'true';
            $company = Company::first();
            $establishment = auth()->user()->establishment;
            $filters = $request;
            $data = $this->getData($request);
            $records = $data['records'];
            $taxes = $this->getTaxesDocuments($records);
            $summary_records = $request->summary_sales_book ? $this->getSummaryRecords($data, $request) : [];
            $report_data = compact('records', 'company', 'establishment', 'filters', 'taxes', 'summary_records');

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
                        return response()->json([
                            'success' => false,
                            'message' => "El reporte tiene " . count($records) . " registros. Para reportes con más de {$maxRecordsForPdf} registros, por favor use la exportación a Excel.",
                            'records_count' => count($records),
                            'max_allowed' => $maxRecordsForPdf
                        ], 400);
                    }

                    // Forzar límite de memoria nuevamente antes de generar PDF
                    ini_set('memory_limit', '2G');

                    $pdf = PDF::loadView('report::co-sales-book.report_pdf', $report_data)->setPaper('a4', 'landscape');
                    $filename = 'Reporte_Libro_Ventas_'.date('YmdHis');

                    $this->freeMemory();
                    return $pdf->stream($filename.'.pdf');
                    break;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

}
