
<table class="">
    <thead>
        <tr>
            <th colspan="9"></th>

            @foreach($taxes as $tax)
                @if($tax && isset($tax->name))
                    <th colspan="2">
                        IMPUESTO #{{ $loop->iteration }}
                        <br>
                        {{ $tax->name ?? 'Impuesto sin nombre' }} - ({{ $tax->rate ?? '0' }}%)
                    </th>
                @endif
            @endforeach
        </tr>
        <tr>
            <th>F. Emisión</th>
            <th>Doc</th>
            <th>Nro</th>
            <th>Nit</th>
            <th>Nombre</th>
            <th>Moneda</th>
            <th>Total/Neto</th>
            <th>Total <br>+<br> Impuesto</th>
            <th>Total/Excento</th>

            @foreach($taxes as $tax)
                @if($tax && isset($tax->name))
                    <th>Base</th>
                    <th>Impuesto</th>
                @endif
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php
            $total = 0;
            $net_total = 0;
            $total_exempt = 0;
        @endphp
        @foreach($records as $value)
            @php
                $row = $value->getDataReportSalesBook();
                $total = $total + $row['total'];
                $net_total = $net_total + $row['net_total'];
                $total_exempt = $total_exempt + $row['total_exempt'];
            @endphp
            <tr>
                <td class="celda">{{ $row['date_of_issue'] }}</td>
                <td class="celda">{{$row['type_document_name']}}</td>
                <td class="celda">{{ $row['number_full'] }}</td>
                <td class="celda">{{ $row['customer_code'] }}</td>
                <td class="celda">{{ $row['customer_name'] }}</td>
                <td class="celda">{{ $row['currency_code'] }}</td>
                <td class="celda text-right-td">{{ $row['net_total'] }}</td>
                <td class="celda text-right-td">{{ $row['total'] }}</td>
                <td class="celda text-right-td"> {{ $row['total_exempt'] }} </td>

                @foreach($taxes as $tax)
                    @if($tax && isset($tax->id) && isset($tax->name))
                        @php
                            $item_values = $value->getItemValuesByTax($tax->id);
                        @endphp

                        <td class="celda text-right-td">{{ $item_values['taxable_amount'] }}</td>
                        <td class="celda text-right-td">{{ $item_values['tax_amount'] }}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
        <tr>
            <th colspan="6" class="celda text-right-td">TOTALES</th>
            <th>{{ $net_total }}</th>
            <th>{{ $total }}</th>
            <th>{{ $total_exempt }}</th>
            @foreach($taxes as $tax)
                @if($tax && isset($tax->name))
                    <th></th>
                    <th></th>
                @endif
            @endforeach
        </tr>
    </tbody>
</table>
<table>

</table>
