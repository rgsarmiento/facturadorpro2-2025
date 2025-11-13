@extends('tenant.layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <div>
                        <h4 class="card-title">Consulta de Compras</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div>
                        <form action="{{route('tenant.reports.purchases.search')}}" class="el-form demo-form-inline el-form--inline" method="GET">
                            <tenant-calendar :document_types="{{json_encode($documentTypes)}}" data_d="{{$d ?? ''}}" :establishments="{{json_encode($establishments)}}" establishment="{{$establishment ?? null}}" data_a="{{$a ?? ''}}" td="{{$td ?? null}}"></tenant-calendar>
                        </form>
                    </div>
                    @if(!empty($reports) && $reports->count())
                    <div class="box">
                        <div class="box-body no-padding">
                            <div style="margin-bottom: 10px">
                                @if(isset($reports))
                                    <form action="{{route('tenant.report.purchases.pdf')}}" class="d-inline" method="POST">
                                        {{csrf_field()}}
                                        <input type="hidden" value="{{$d}}" name="d">
                                        <input type="hidden" value="{{$a}}" name="a">
                                        <input type="hidden" value="{{$establishment}}" name="establishment">
                                        <input type="hidden" value="{{$td}}" name="td">
                                        <button class="btn btn-custom   mt-2 mr-2" type="submit"><i class="fa fa-file-pdf"></i> Exportar PDF</button>
                                        {{-- <label class="pull-right">Se encontraron {{$reports->count()}} registros.</label> --}}
                                    </form>
                                <form action="{{route('tenant.report.purchases.report_excel')}}" class="d-inline" method="POST">
                                    {{csrf_field()}}
                                    <input type="hidden" value="{{$d}}" name="d">
                                    <input type="hidden" value="{{$td}}" name="td">
                                    <input type="hidden" value="{{$establishment}}" name="establishment">
                                    <input type="hidden" value="{{$a}} " name="a">
                                    <button class="btn btn-custom   mt-2 mr-2" type="submit"><i class="fa fa-file-excel"></i> Exportar Excel</button>
                                    {{-- <label class="pull-right">Se encontraron {{$reports->count()}} registros.</label> --}}
                                </form>
                                @endif
                            </div>
                            <table width="100%" class="table table-striped table-responsive-xl table-bordered table-hover">
                                <thead class="">
                                    <tr>
                                        <th class="">#</th>
                                        <th class="sorting" onclick="sortTable('document_type_id')" style="cursor: pointer;">
                                            Tipo Documento
                                            @if(request()->sort_column === 'document_type_id')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sorting" onclick="sortTable('series')" style="cursor: pointer;">
                                            Número
                                            @if(request()->sort_column === 'series')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sorting" onclick="sortTable('date_of_issue')" style="cursor: pointer;">
                                            F. Emisión
                                            @if(request()->sort_column === 'date_of_issue')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sorting" onclick="sortTable('date_of_due')" style="cursor: pointer;">
                                            F. Vencimiento
                                            @if(request()->sort_column === 'date_of_due')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>

                                        <th class="sorting" onclick="sortTable('supplier_id')" style="cursor: pointer;">
                                            Cliente
                                            @if(request()->sort_column === 'supplier_id')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="">RUC</th>
                                        <th class="">F. Pago</th>
                                        <th class="sorting" onclick="sortTable('state_type_id')" style="cursor: pointer;">
                                            Estado
                                            @if(request()->sort_column === 'state_type_id')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sorting" onclick="sortTable('total_exonerated')" style="cursor: pointer;">
                                            T.Exonerado
                                            @if(request()->sort_column === 'total_exonerated')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>

                                        <th class="sorting" onclick="sortTable('total_unaffected')" style="cursor: pointer;">
                                            T.Inafecta
                                            @if(request()->sort_column === 'total_unaffected')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sorting" onclick="sortTable('total_free')" style="cursor: pointer;">
                                            T.Gratuito
                                            @if(request()->sort_column === 'total_free')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sorting" onclick="sortTable('total_taxed')" style="cursor: pointer;">
                                            Total Gravado
                                            @if(request()->sort_column === 'total_taxed')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sorting" onclick="sortTable('total_igv')" style="cursor: pointer;">
                                            Total IGV
                                            @if(request()->sort_column === 'total_igv')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                        <th class="sorting" onclick="sortTable('total')" style="cursor: pointer;">
                                            Total
                                            @if(request()->sort_column === 'total')
                                                @if(request()->sort_direction === 'asc')
                                                    <i class="fa fa-caret-up"></i>
                                                @else
                                                    <i class="fa fa-caret-down"></i>
                                                @endif
                                            @else
                                                <i class="fa fa-arrows-v text-muted"></i>
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reports as $key => $value)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$value->document_type->id}}</td>
                                        <td>{{$value->series}}-{{$value->number}}</td>
                                        <td>{{$value->date_of_issue->format('Y-m-d')}}</td>
                                        <td>{{$value->date_of_due->format('Y-m-d')}}</td>

                                        <td>{{$value->supplier->name}}</td>
                                        <td>{{$value->supplier->number}}</td>
                                        <td>{{isset($value->purchase_payments['payment_method_type']['description'])?$value->purchase_payments['payment_method_type']['description']:'-'}}</td>
                                        <td>{{$value->state_type->description}}</td>
                                        <td>{{ $value->total_exonerated}}</td>

                                        <td>{{ $value->total_unaffected}}</td>
                                        <td>{{ $value->total_free}}</td>
                                        <td>{{ $value->state_type_id == '11' ? 0 : $value->total_taxed}}</td>
                                        <td>{{ $value->state_type_id == '11' ? 0 : $value->total_igv}}</td>
                                        <td>{{ $value->state_type_id == '11' ? 0 : $value->total}}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            Total {{$reports->total()}}
                            <label class="pagination-wrapper ml-2">
                                {{-- {{ $reports->appends(['search' => Session::get('form_document_list')])->render()  }} --}}
                                {{$reports->appends($_GET)->render()}}
                            </label>
                        </div>
                    </div>
                    @else
                    <div class="box box-body no-padding">
                        <strong>No se encontraron registros</strong>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function sortTable(column) {
            const currentSort = '{{ request()->sort_column }}';
            const currentDirection = '{{ request()->sort_direction }}';

            let newDirection = 'asc';
            if (currentSort === column && currentDirection === 'asc') {
                newDirection = 'desc';
            }

            // Get current form values
            const d = document.querySelector('input[name="d"]')?.value || '';
            const a = document.querySelector('input[name="a"]')?.value || '';
            const establishment = document.querySelector('select[name="establishment"]')?.value || '';
            const td = document.querySelector('select[name="td"]')?.value || '';

            // Build URL with sorting parameters
            const params = new URLSearchParams();
            if (d) params.append('d', d);
            if (a) params.append('a', a);
            if (establishment) params.append('establishment', establishment);
            if (td) params.append('td', td);
            params.append('sort_column', column);
            params.append('sort_direction', newDirection);

            window.location.href = '{{ route("tenant.reports.purchases.search") }}?' + params.toString();
        }
    </script>
@endpush
