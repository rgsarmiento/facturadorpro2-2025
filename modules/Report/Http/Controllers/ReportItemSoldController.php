<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Company;
use Modules\Report\Exports\ItemSoldExport;
use Modules\Report\Traits\PdfMemoryManagement;
use Carbon\Carbon;
use App\Models\Tenant\{
    DocumentItem,
    DocumentPosItem
};
use DB;

class ReportItemSoldController extends Controller
{
    use PdfMemoryManagement;


    public function index()
    {
        return view('report::co-items-sold.index');
    }

    /**
     *
     * @param  Request $request
     * @return Collection
     */
    public function getQueryRecords($request)
    {
        $document_type_id = $request->document_type_id ?? null;
        $records = [];
        switch ($document_type_id)
        {
            case 'documents':
                $records = DocumentItem::filterReportSoldItems($request)->get();
                break;

            case 'documents_pos':
                $records = DocumentPosItem::filterReportSoldItems($request)->get();
                break;

            default:
                $document_items = DocumentItem::filterReportSoldItems($request)->get();
                $document_items_pos = DocumentPosItem::filterReportSoldItems($request)->get();
                $records = $document_items->concat($document_items_pos);
                break;
        }

//        // Agrupación por item_id
//        $grouped = $records->groupBy('item_id')->map(function ($group) {
//            $first = $group->first();
//            $first->total_quantity = $group->sum('quantity');
//            return $first;
//        })->values();
//
//        return $grouped;
        return $records;
    }

    public function export(Request $request, $type)
    {
        try {
            // Configurar recursos para generación de PDFs (memoria y tiempo)
            $this->configurePdfResources('2G', 600);

            switch ($type) {
                case 'excel':
                    return $this->excel($request);
                    break;

                default:
                    // Verificar cantidad de registros antes de generar PDF
                    $records = $this->getQueryRecords($request);
                    $maxRecordsForPdf = 10000;

                    if ($records->count() > $maxRecordsForPdf) {
                        return response()->json([
                            'success' => false,
                            'message' => "El reporte tiene " . $records->count() . " registros. Para reportes con más de {$maxRecordsForPdf} registros, por favor use la exportación a Excel.",
                            'records_count' => $records->count(),
                            'max_allowed' => $maxRecordsForPdf
                        ], 400);
                    }

                    // Forzar límite de memoria nuevamente antes de generar PDF
                    ini_set('memory_limit', '2G');

                    $result = $this->pdf($request);
                    $this->freeMemory();
                    return $result;
                    break;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     *
     * @param  Request $request
     * @return mixed
     */
    public function pdf(Request $request){
        $records = $this->getQueryRecords($request);
        // Agrupamos por item_id de forma segura, evitando acceder a item->id cuando la relación no esté cargada
        $grouped = $records
            ->groupBy(function ($record) {
                return $record->item_id ?? ($record->item->id ?? 'sin_id');
            })
            ->map(function ($items) {
                $first = $items->first();
                // Acceso seguro a la relación item (puede no existir en algunos casos)
                $itemData = isset($first->item) ? $first->item : null;

                // Cantidad total vendida del ítem
                $quantitySum = $items->sum(function ($it) { return (float) ($it->quantity ?? 0); });

                // Costo total (precio de compra * cantidad) usando relation_item->purchase_unit_price
                $costSum = $items->sum(function ($it) {
                    $purchaseUnit = optional($it->relation_item)->purchase_unit_price ?? 0;
                    return (float) $purchaseUnit * (float) ($it->quantity ?? 0);
                });

                // Valor neto total (usa accessor net_value = quantity * unit_price)
                $netValueSum = $items->sum(function ($it) { return (float) ($it->net_value ?? 0); });

                // Utilidad agregada (neto - costo)
                $utilitySum = $netValueSum - $costSum;

                // Total de línea (suma de total por item - ya incluye impuestos/ descuentos aplicados a nivel de item)
                $totalSum = $items->sum(function ($it) { return (float) ($it->total ?? 0); });

                // Impuesto total (suma total_tax)
                $taxSum = $items->sum(function ($it) { return (float) ($it->total_tax ?? 0); });

                // Descuento total (sumar numérico; si viene estructurado, ignorar)
                $discountSum = $items->sum(function ($it) {
                    return is_array($it->discount) ? 0 : (float) ($it->discount ?? 0);
                });

                return [
                    'type_name'   => $itemData->type_name ?? '',
                    'internal_id' => $itemData->internal_id ?? ($first->internal_id ?? ''),
                    'name'        => $itemData->name ?? ($first->item_name ?? $first->description ?? ''),
                    'quantity'    => $quantitySum,
                    'cost'        => $costSum,
                    'net_value'   => $netValueSum,
                    'utility'     => $utilitySum,
                    'total_tax'   => $taxSum,
                    'discount'    => $discountSum,
                    'total'       => $totalSum,
                ];
            })
            ->sortBy('name')
            ->values();
        $filters = $request;
        $company = Company::first();
        $establishment = auth()->user()->establishment;
        $pdf = PDF::loadView('report::co-items-sold.report_pdf', compact('grouped', 'company', 'establishment', 'filters'))
            ->setPaper('a4', 'landscape');
        $filename = 'Reporte_Articulos_Vendidos_' . date('YmdHis');
        return $pdf->stream($filename . '.pdf');
    }


    /**
     * Excel
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function excel(Request $request) {
        $records = $this->getQueryRecords($request);
        $filters = $request;
        $company = Company::first();
        $establishment = auth()->user()->establishment;
        return (new ItemSoldExport)
            ->records($records)
            ->company($company)
            ->establishment($establishment)
            ->filters($filters)
            ->download('ReporteArticulosVendidos'.Carbon::now().'.xlsx');
    }
}
