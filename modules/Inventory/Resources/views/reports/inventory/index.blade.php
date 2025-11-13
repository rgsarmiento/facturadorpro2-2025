@extends('tenant.layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <div>
                        <h4 class="card-title">Consulta de inventarios</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div>
                        <form action="{{route('reports.inventory.search')}}" class="el-form demo-form-inline el-form--inline" method="POST">
                            {{csrf_field()}}
                            {{-- <div class="el-form-item col-xs-12">
                                <div class="el-form-item__content">
                                    <button class="btn btn-custom" type="submit"><i class="fa fa-search"></i> Buscar</button>
                                </div>
                            </div> --}}
                        </form>
                    </div>
                    <div class="box">
                        <div class="box-body no-padding">
                            <div style="margin-bottom: 10px" class="row">
                                <div style="padding-top: 0.5%" class="col-md-6">
                                    <form action="{{route('reports.inventory.index')}}" method="get">
                                        {{csrf_field()}}
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="">Establecimiento</label>
                                                <select class="form-control" name="warehouse_id" id="">
                                                    <option {{ request()->warehouse_id == 'all' ?  'selected' : ''}} selected value="all">Todos</option>
                                                    @foreach($warehouses as $item)
                                                    <option {{ request()->warehouse_id == $item->id ?  'selected' : ''}} value="{{$item->id}}">{{$item->description}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="filter">Características</label>
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
                                            <div class="col-md-4">
                                                <label for="filter">Fecha</label>
                                                <input name="date" value="{{ request()->date ? request()->date : ''}}" type="text" data-plugin-datepicker class="form-control">
                                            </div>
                                            <div class="col-md-4"> <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Buscar</button></div>
                                        </div>
                                    </form>
                                </div>
                                @if(!empty($reports) && $reports->count())
                                    @if(isset($reports))
                                        <div class="col-md-4">
                                            <form action="{{route('reports.inventory.pdf')}}" class="d-inline" method="POST">
                                                {{csrf_field()}}
                                                <input type="hidden" name="warehouse_id" value="{{request()->warehouse_id ? request()->warehouse_id : 'all'}}">
                                                <input type="hidden" name="filter" value="{{request()->filter ? request()->filter : ''}}">
                                                <input type="hidden" name="date" value="{{request()->date ? request()->date : ''}}">
                                                <button class="btn btn-custom   mt-2 mr-2" type="submit"><i class="fa fa-file-pdf"></i> Exportar PDF</button>
                                                {{-- <label class="pull-right">Se encontraron {{$reports->count()}} registros.</label> --}}
                                            </form>

                                            <form action="{{route('reports.inventory.report_excel')}}" class="d-inline" method="POST">
                                                {{csrf_field()}}
                                                <input type="hidden" name="warehouse_id" value="{{request()->warehouse_id ? request()->warehouse_id : 'all'}}">
                                                <input type="hidden" name="filter" value="{{request()->filter ? request()->filter : ''}}">
                                                <input type="hidden" name="date" value="{{request()->date ? request()->date : ''}}">
                                                <button class="btn btn-custom   mt-2 mr-2" type="submit"><i class="fa fa-file-excel"></i> Exportar Excel</button>
                                                {{-- <label class="pull-right">Se encontraron {{$reports->count()}} registros.</label> --}}
                                            </form>
                                        </div>

                                    @endif
                                @endif
                            </div>
                            <table width="100%" class="table table-striped table-responsive-xl table-bordered table-hover">
                                <thead class="">
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

                                    {{-- @php
                                        $total_global_sale_unit_price = 0;
                                        $total_global_purchase_unit_price = 0;
                                    @endphp --}}
                                    @if(!empty($reports) && $reports->count())
                                        @foreach($reports as $key => $value)

                                            @php
                                                $global_sale_unit_price = $value->getGlobalSaleUnitPrice();
                                                $global_purchase_unit_price = $value->getGlobalPurchaseUnitPrice();

                                                // $total_global_sale_unit_price += $global_sale_unit_price;
                                                // $total_global_purchase_unit_price += $global_purchase_unit_price;
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
                                            <td colspan="8">No se encontraron registros</td>
                                        </tr>
                                    @endif
                                    {{-- <tr>
                                        <td class="celda" colspan="5"></td>
                                        <td class="celda">Total</td>
                                        <td class="celda text-right">{{ number_format($total_global_sale_unit_price, 6, ".", "") }}</td>
                                        <td class="celda text-right">{{ number_format($total_global_purchase_unit_price, 6, ".", "") }}</td>
                                    </tr> --}}

                                </tbody>
                            </table>
                            <style>
                                th.sorting {
                                    cursor: pointer;
                                }

                                th.sorting:hover {
                                    background-color: #f5f5f5;
                                }

                                th.sorting-active {
                                    background-color: #e8f4f8;
                                    font-weight: bold;
                                }
                            </style>
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
                            Total {{$reports->total()}}
                            <label class="pagination-wrapper ml-2">
                                {{$reports->appends($_GET)->render()}}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
