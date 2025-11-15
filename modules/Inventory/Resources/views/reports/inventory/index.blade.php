@extends('tenant.layouts.app')

@section('content')
    <div class="inventory-report-form">
        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0 text-white"><i class="fas fa-boxes"></i> Consulta de Inventarios</h3>
            </div>
            <div class="card-body">
                <!-- Sección 1: Filtros de Búsqueda -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-filter"></i>
                        <span>Filtros de Búsqueda</span>
                    </div>
                    <form action="{{route('reports.inventory.index')}}" method="get">
                        {{csrf_field()}}
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Establecimiento</label>
                                    <select class="form-control" name="warehouse_id">
                                        <option {{ request()->warehouse_id == 'all' ?  'selected' : ''}} selected value="all">Todos</option>
                                        @foreach($warehouses as $item)
                                        <option {{ request()->warehouse_id == $item->id ?  'selected' : ''}} value="{{$item->id}}">{{$item->description}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Características</label>
                                    <select class="form-control" id="filter" name="filter">
                                        <option value="">--</option>
                                        @foreach ($filter as $group => $items)
                                            <optgroup label="@lang('app.'.$group)">
                                                @forelse ($items as $item)
                                                    <option
                                                        {{ request()->filter ==  $group . '_' . $item['id'] ?  'selected' : ''}}
                                                        value="{{ $group . '_' . $item['id'] }}">
                                                        {{ $item['name'] }}
                                                    </option>
                                                @empty
                                                    <option disabled>No hay @lang('app.'.$group) disponibles</option>
                                                @endforelse
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Fecha</label>
                                    <input name="date" value="{{ request()->date ? request()->date : ''}}" type="text" data-plugin-datepicker class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label d-block">&nbsp;</label>
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Buscar</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Sección 2: Acciones de Exportación -->
                @if(!empty($reports) && $reports->count())
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-file-export"></i>
                        <span>Exportar Reportes</span>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <form action="{{route('reports.inventory.pdf')}}" class="d-inline" method="POST">
                                {{csrf_field()}}
                                <input type="hidden" name="warehouse_id" value="{{request()->warehouse_id ? request()->warehouse_id : 'all'}}">
                                <input type="hidden" name="filter" value="{{request()->filter ? request()->filter : ''}}">
                                <input type="hidden" name="date" value="{{request()->date ? request()->date : ''}}">
                                <button class="btn btn-danger mr-2" type="submit"><i class="fa fa-file-pdf"></i> Exportar PDF</button>
                            </form>

                            <form action="{{route('reports.inventory.report_excel')}}" class="d-inline" method="POST">
                                {{csrf_field()}}
                                <input type="hidden" name="warehouse_id" value="{{request()->warehouse_id ? request()->warehouse_id : 'all'}}">
                                <input type="hidden" name="filter" value="{{request()->filter ? request()->filter : ''}}">
                                <input type="hidden" name="date" value="{{request()->date ? request()->date : ''}}">
                                <button class="btn btn-success" type="submit"><i class="fa fa-file-excel"></i> Exportar Excel</button>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Sección 3: Resultados -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-table"></i>
                        <span>Resultados del Inventario</span>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th class="@if(request()->sort_column == 'item_description') sorting sorting-active @else sorting @endif" onclick="sortTable('item_description')" style="cursor: pointer;">Descripción <i class="@if(request()->sort_column == 'item_description') @if(request()->sort_direction == 'asc') el-icon-caret-top @else el-icon-caret-bottom @endif @else el-icon-d-caret @endif" style="margin-left: 5px;"></i></th>
                                            <th class="@if(request()->sort_column == 'stock') sorting sorting-active @else sorting @endif text-right" onclick="sortTable('stock')" style="cursor: pointer;">Inventario actual <i class="@if(request()->sort_column == 'stock') @if(request()->sort_direction == 'asc') el-icon-caret-top @else el-icon-caret-bottom @endif @else el-icon-d-caret @endif" style="margin-left: 5px;"></i></th>
                                            <th class="@if(request()->sort_column == 'sale_unit_price') sorting sorting-active @else sorting @endif text-right" onclick="sortTable('sale_unit_price')" style="cursor: pointer;">Precio de venta <i class="@if(request()->sort_column == 'sale_unit_price') @if(request()->sort_direction == 'asc') el-icon-caret-top @else el-icon-caret-bottom @endif @else el-icon-d-caret @endif" style="margin-left: 5px;"></i></th>
                                            <th class="@if(request()->sort_column == 'purchase_unit_price') sorting sorting-active @else sorting @endif text-right" onclick="sortTable('purchase_unit_price')" style="cursor: pointer;">Costo <i class="@if(request()->sort_column == 'purchase_unit_price') @if(request()->sort_direction == 'asc') el-icon-caret-top @else el-icon-caret-bottom @endif @else el-icon-d-caret @endif" style="margin-left: 5px;"></i></th>
                                            <th class="@if(request()->sort_column == 'warehouse_description') sorting sorting-active @else sorting @endif" onclick="sortTable('warehouse_description')" style="cursor: pointer;">Almacén <i class="@if(request()->sort_column == 'warehouse_description') @if(request()->sort_direction == 'asc') el-icon-caret-top @else el-icon-caret-bottom @endif @else el-icon-d-caret @endif" style="margin-left: 5px;"></i></th>
                                            <th class="text-right">
                                                Precio de venta Global
                                                <el-tooltip class="item" effect="dark" content="Precio de venta * Inventario actual (Stock)" placement="top-start">
                                                    <i class="fa fa-info-circle"></i>
                                                </el-tooltip>
                                            </th>
                                            <th class="text-right">
                                                Costo Global
                                                <el-tooltip class="item" effect="dark" content="Costo * Inventario actual (Stock)" placement="top-start">
                                                    <i class="fa fa-info-circle"></i>
                                                </el-tooltip>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($reports) && $reports->count())
                                            @foreach($reports as $key => $value)
                                                @php
                                                    $global_sale_unit_price = $value->getGlobalSaleUnitPrice();
                                                    $global_purchase_unit_price = $value->getGlobalPurchaseUnitPrice();
                                                @endphp
                                                <tr>
                                                    <td class="celda">{{$loop->iteration}}</td>
                                                    <td class="celda">{{$value->item->internal_id ?? ''}} {{$value->item->internal_id ? '-':''}} {{$value->item->name ?? ''}}</td>
                                                    <td class="celda text-right">{{number_format($value->stock, 2)}}</td>
                                                    <td class="celda text-right">{{number_format($value->item->sale_unit_price, 2)}}</td>
                                                    <td class="celda text-right">{{number_format($value->item->purchase_unit_price, 2)}}</td>
                                                    <td class="celda">{{$value->warehouse->description}}</td>
                                                    <td class="celda text-right">{{number_format($global_sale_unit_price, 2)}}</td>
                                                    <td class="celda text-right">{{number_format($global_purchase_unit_price, 2)}}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="8" class="text-center">No se encontraron registros</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>

                                @if(!empty($reports) && $reports->count())
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <strong>Total: {{$reports->total()}} registros</strong>
                                    </div>
                                    <div>
                                        {{$reports->appends($_GET)->render()}}
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('styles')
<style>
/* Diseño profesional para Reporte de Inventario */
.inventory-report-form {
    background: #f8f9fa;
}

.inventory-report-form .form-section {
    background: white !important;
    padding: 20px !important;
    margin-bottom: 20px !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    border-left: 4px solid #409EFF !important;
}

.inventory-report-form .section-header {
    font-size: 18px !important;
    font-weight: 600 !important;
    color: #2c3e50 !important;
    margin: 0 0 20px 0 !important;
    padding-bottom: 10px !important;
    border-bottom: 2px solid #e9ecef !important;
    display: flex !important;
    align-items: center !important;
}

.inventory-report-form .section-header i {
    color: #409EFF !important;
    margin-right: 10px !important;
    font-size: 20px !important;
}

.inventory-report-form .form-section:nth-child(2) { border-left-color: #67C23A !important; }
.inventory-report-form .form-section:nth-child(3) { border-left-color: #E6A23C !important; }

.inventory-report-form .form-section:nth-child(2) .section-header i {
    color: #67C23A !important;
}

.inventory-report-form .form-section:nth-child(3) .section-header i {
    color: #E6A23C !important;
}

.form-group {
    margin-bottom: 15px;
}

.control-label {
    display: block;
    font-weight: 500;
    color: #606266;
    margin-bottom: 8px;
}

th.sorting {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s;
}

th.sorting:hover {
    background-color: #f5f5f5;
}

th.sorting-active {
    background-color: #e8f4f8;
    font-weight: 600;
}
</style>
@endpush

<script>
    function sortTable(column) {
        const currentSort = '{{ request()->sort_column }}';
        const currentDirection = '{{ request()->sort_direction }}';
        const newDirection = (currentSort === column && currentDirection === 'asc') ? 'desc' : 'asc';

        const form = document.createElement('form');
        form.method = 'GET';
        form.action = '{{ route("reports.inventory.index") }}';

        const warehouseInput = document.createElement('input');
        warehouseInput.type = 'hidden';
        warehouseInput.name = 'warehouse_id';
        warehouseInput.value = '{{ request()->warehouse_id ?? "all" }}';
        form.appendChild(warehouseInput);

        const filterInput = document.createElement('input');
        filterInput.type = 'hidden';
        filterInput.name = 'filter';
        filterInput.value = '{{ request()->filter ?? "" }}';
        form.appendChild(filterInput);

        const dateInput = document.createElement('input');
        dateInput.type = 'hidden';
        dateInput.name = 'date';
        dateInput.value = '{{ request()->date ?? "" }}';
        form.appendChild(dateInput);

        const sortColumnInput = document.createElement('input');
        sortColumnInput.type = 'hidden';
        sortColumnInput.name = 'sort_column';
        sortColumnInput.value = column;
        form.appendChild(sortColumnInput);

        const sortDirectionInput = document.createElement('input');
        sortDirectionInput.type = 'hidden';
        sortDirectionInput.name = 'sort_direction';
        sortDirectionInput.value = newDirection;
        form.appendChild(sortDirectionInput);

        document.body.appendChild(form);
        form.submit();
    }
</script>
@endsection

@push('scripts')
    <script>
        // Datepicker
        (function($) {

        'use strict';

        $(document).ready(function () {
            // Inicializar Bootstrap Datepicker
            $('[data-plugin-datepicker]').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                language: 'es'
            });
        });

        // if ( $.isFunction($.fn[ 'bootstrapDP' ]) ) {

        //     $(function() {
        //         $('[data-plugin-datepicker]').each(function() {
        //             var $this = $( this ),
        //                 opts = {};

        //             var pluginOptions = $this.data('plugin-options');
        //             if (pluginOptions)
        //                 opts = pluginOptions;

        //             $this.themePluginDatePicker(opts);
        //         });
        //     });

        // }

        }).apply(this, [jQuery]);
    </script>
@endpush
