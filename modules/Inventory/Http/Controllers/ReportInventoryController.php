<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Company;
use App\Models\Tenant\Item;
use Modules\Inventory\Models\ItemWarehouse;
use Modules\Inventory\Exports\InventoryExport;
use Modules\Inventory\Models\Warehouse;
use Modules\Item\Models\Category;
use Modules\Item\Models\Brand;
use Modules\Item\Models\Color;
use Modules\Item\Models\Size;



use Carbon\Carbon;

class ReportInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $categories = Category::select('id', 'name')->get();
        $brands = Brand::select('id', 'name')->get();
        $colors = Color::select('id', 'name')->get();
        $sizes = Size::select('id', 'name')->get();
        $filter = [
            'category' => $categories,
            'brand' => $brands,
            'color' => $colors,
            'size' => $sizes
        ];
        $date = $request->date;

        [$relation, $id] = explode('_', $request->filter) + [null, null];

        if($request->warehouse_id && $request->warehouse_id != 'all') {
            $reports = ItemWarehouse::with(['item', 'warehouse'])
                ->where('warehouse_id', $request->warehouse_id)
                ->whereFilterDate($date)
                ->whereHas('item', function($q) use ($relation, $id) {
                    $q->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']]);
                    $q->whereNotIsSet();
                    $q->whereFilterByRelation($relation, $id);
                });
        }
        else {
            $reports = ItemWarehouse::with(['item', 'warehouse'])
                ->whereFilterDate($date)
                ->whereHas('item',function($q) use ($relation, $id){
                    $q->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']]);
                    $q->whereNotIsSet();
                    $q->whereFilterByRelation($relation, $id);
                });
        }

        // Apply sorting
        if ($request->has('sort_column') && $request->sort_column) {
            $sortColumn = $request->sort_column;
            $sortDirection = in_array($request->sort_direction, ['asc', 'desc']) ? $request->sort_direction : 'asc';

            // Numeric columns that should be sorted as numbers
            $numericColumns = ['stock', 'sale_unit_price', 'purchase_unit_price'];

            if ($sortColumn === 'item_description') {
                $reports = $reports->whereHas('item', function($q) use ($sortDirection) {
                    $q->orderBy('name', $sortDirection);
                })->orderBy('warehouse_id');
            } elseif ($sortColumn === 'warehouse_description') {
                $reports = $reports->orderBy(\DB::raw('(SELECT description FROM warehouses WHERE warehouses.id = item_warehouse.warehouse_id)'), $sortDirection);
            } elseif ($sortColumn === 'stock') {
                $reports = $reports->orderByRaw("CAST(item_warehouse.stock AS DECIMAL(10,2)) {$sortDirection}");
            } elseif ($sortColumn === 'sale_unit_price') {
                $reports = $reports->whereHas('item', function($q) use ($sortDirection) {
                    $q->orderByRaw("CAST(items.sale_unit_price AS DECIMAL(10,2)) {$sortDirection}");
                });
            } elseif ($sortColumn === 'purchase_unit_price') {
                $reports = $reports->whereHas('item', function($q) use ($sortDirection) {
                    $q->orderByRaw("CAST(items.purchase_unit_price AS DECIMAL(10,2)) {$sortDirection}");
                });
            }
        } else {
            $reports = $reports->latest();
        }

        $reports = $reports->paginate(config('tenant.items_per_page'));
        $warehouses = Warehouse::select('id', 'description')->get();

        return view('inventory::reports.inventory.index', compact('reports', 'warehouses', 'filter'));
    }

    /**
     * Search
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request) {

        $reports = ItemWarehouse::with(['item'])->whereHas('item', function($q){
            $q->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']]);
            $q->whereNotIsSet();
        })->latest()->get();

        return view('inventory::reports.inventory.index', compact('reports'));
    }

    /**
     * PDF
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function pdf(Request $request) {
        $company = Company::first();
        $establishment = Establishment::first();
        ini_set('max_execution_time', 0);
//        [$relation, $id] = explode('_', $request->filter) + [null, null];
        [$relation, $id] = array_pad(explode('_', $request->filter ?? ''), 2, null);
        if($request->warehouse_id && $request->warehouse_id != 'all'){
            $records = ItemWarehouse::with(['item'])
                ->where('warehouse_id', $request->warehouse_id)
                ->whereFilterDate($request->date)
                ->whereHas('item', function($q) use ($relation, $id) {
                    $q->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']]);
                    $q->whereNotIsSet();
                    $q->whereFilterByRelation($relation, $id);
                })
                ->latest()
                ->get();
        }
        else {
            $records = ItemWarehouse::with(['item'])
                ->whereFilterDate($request->date)
                ->whereHas('item', function($q) use ($relation, $id) {
                    $q->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']]);
                    $q->whereNotIsSet();
                    $q->whereFilterByRelation($relation, $id);
                })
                ->latest()
                ->get();
        }
        $pdf = PDF::loadView('inventory::reports.inventory.report_pdf', compact("records", "company", "establishment"))->setPaper('a4', 'landscape');
        $filename = 'Reporte_Inventario'.date('YmdHis');
        return $pdf->download($filename.'.pdf');
    }

    /**
     * Excel
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function excel(Request $request) {
        $company = Company::first();
        $establishment = Establishment::first();
        ini_set('max_execution_time', 0);
//        [$relation, $id] = explode('_', $request->filter) + [null, null];
        [$relation, $id] = array_pad(explode('_', $request->filter ?? ''), 2, null);
        if($request->warehouse_id && $request->warehouse_id != 'all'){
            $records = ItemWarehouse::with(['item'])
                ->where('warehouse_id', $request->warehouse_id)
                ->whereFilterDate($request->date)
                ->whereHas('item', function($q) use ($relation, $id) {
                    $q->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']]);
                    $q->whereNotIsSet();
                    $q->whereFilterByRelation($relation, $id);
                })
                ->latest()
                ->get();
        }
        else {
            $records = ItemWarehouse::with(['item'])
                ->whereFilterDate($request->date)
                ->whereHas('item', function($q) use ($relation, $id) {
                    $q->where([['item_type_id', '01'], ['unit_type_id', '!=','ZZ']]);
                    $q->whereNotIsSet();
                    $q->whereFilterByRelation($relation, $id);
                })
                ->latest()
                ->get();
        }
        return (new InventoryExport)
            ->records($records)
            ->company($company)
            ->establishment($establishment)
            ->download('ReporteInv'.Carbon::now().'.xlsx');
    }
}
