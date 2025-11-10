<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Artículos Vendidos</title>

    @include('report::commons.styles')
</head>
<body>
    @include('report::commons.header')

    <div>
        <p align="left" class="title"><strong>Artículos Vendidos</strong></p>
    </div>

    @include('report::co-items-sold.partials.filters')

    @if($grouped->count() > 0)
        <div>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Código</th>
                        <th>Artículo</th>
                        <th>Cantidad</th>
                        <th>Costo</th>
                        <th>Neto</th>
                        <th>Utilidad</th>
                        <th>Impuesto</th>
                        <th>Descuento</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalQuantity = 0;
                        $totalCost = 0;
                        $totalNetValue = 0;
                        $totalUtility = 0;
                        $totalTax = 0;
                        $totalDiscount = 0;
                        $grandTotal = 0;
                    @endphp

                    @foreach($grouped as $row)
                        @php
                            $totalQuantity += $row['quantity'];
                            $totalCost += $row['cost'];
                            $totalNetValue += $row['net_value'];
                            $totalUtility += $row['utility'];
                            $totalTax += $row['total_tax'];
                            $totalDiscount += is_array($row['discount']) ? 0 : $row['discount'];
                            $grandTotal += ($row['total'] * $row['quantity']);
                        @endphp
                        <tr>
                            <td class="celda">{{ $row['type_name'] }}</td>
                            <td class="celda">{{ $row['internal_id'] }}</td>
                            <td class="celda">{{ $row['name'] }}</td>
                            <td class="celda">{{ number_format($row['quantity'], 0, ',', '.') }}</td>
                            <td class="celda">{{ number_format($row['cost'], 2, ',', '.') }}</td>
                            <td class="celda">{{ number_format($row['net_value'], 2, ',', '.') }}</td>
                            <td class="celda">{{ number_format($row['utility'], 2, ',', '.') }}</td>
                            <td class="celda">{{ number_format($row['total_tax'], 2, ',', '.') }}</td>
                            <td class="celda">
                                {{ is_array($row['discount']) ? 'N/A' : number_format($row['discount'], 2, ',', '.') }}
                            </td>
                            <td class="celda">{{ number_format($row['total'] * $row['quantity'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td class="celda" colspan="3"><strong>Total:</strong></td>
                        <td class="celda"><strong>{{ number_format($totalQuantity, 0, ',', '.') }}</strong></td>
                        <td class="celda"><strong>{{ number_format($totalCost, 2, ',', '.') }}</strong></td>
                        <td class="celda"><strong>{{ number_format($totalNetValue, 2, ',', '.') }}</strong></td>
                        <td class="celda"><strong>{{ number_format($totalUtility, 2, ',', '.') }}</strong></td>
                        <td class="celda"><strong>{{ number_format($totalTax, 2, ',', '.') }}</strong></td>
                        <td class="celda"><strong>{{ number_format($totalDiscount, 2, ',', '.') }}</strong></td>
                        <td class="celda"><strong>{{ number_format($grandTotal, 2, ',', '.') }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="callout callout-info">
            <p><strong>No se encontraron registros.</strong></p>
        </div>
    @endif
</body>
</html>
