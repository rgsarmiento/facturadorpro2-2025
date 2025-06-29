@php
    use Carbon\Carbon;

    if(!is_null($sucursal->establishment_logo)){
        if(file_exists(public_path('storage/uploads/logos/'.$sucursal->id."_".$sucursal->establishment_logo)))
            $filename_logo = public_path('storage/uploads/logos/'.$sucursal->id."_".$sucursal->establishment_logo);
        else
            $filename_logo = public_path("storage/uploads/logos/{$company->logo}");
    }
    else
        $filename_logo = public_path("storage/uploads/logos/{$company->logo}");
@endphp
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
          width: 56mm;
          font-family: Arial, sans-serif;
          font-size: 11px;
          margin: 0 auto;
        }

        table {
          width: 100%;
          border-collapse: collapse;
        }

        .text-center {
          text-align: center;
          font-size: 10px !important;
        }

        .text-left {
          text-align: left;
        }

        .text-right {
          text-align: right;
        }

        .border-top-bottom {
          border-top: 1px solid #000;
          border-bottom: 1px solid #000;
        }

        .border-bottom {
          border-bottom: 1px solid #000;
        }

        .desc-9 {
          font-size: 9px;
        }

        td {
          padding: 2px 4px;
        }

        .company_logo {
          max-width: 30mm;
          height: auto;
        }

        .text{
           font-size: 10px !important;
        }
</style>

</head>
<body>
    @if(file_exists($filename_logo))
        <div class="text-center company_logo_box">
            <img src="data:{{ mime_content_type($filename_logo) }};base64,{{ base64_encode(file_get_contents($filename_logo)) }}" alt="{{ $company->name }}" class="company_logo">
        </div>
    @endif

    <table>
        <tr>
            <td colspan="2" class="text-center"><h4 style="margin:0;">{{ $company->name }}</h4></td>
        </tr>
        <tr>
            <td colspan="2" class="text-center"><h5 style="margin:0;">Nit: {{ $company->identification_number }}</h5></td>
        </tr>
        <tr>
            <td colspan="2" class="text-center"><h6 style="margin:0;">{{ ($sucursal->email !== '-') ? $sucursal->email : '' }}</h6></td>
        </tr>
        <tr>
            <td colspan="2" class="text-center"><h6 style="margin:0;">{{ $sucursal->description }}. {{ $sucursal->address }}</h6></td>
        </tr>

        <tr>
            <td><h6 style="margin:0;" class="text">Fecha: {{$date_of_issue}}</h6></td>
            <td><h6 style="margin:0;" class="text">Hora: {{ $created_at}}</h6></td>
        </tr>

        <tr>
            <td><h6 style="margin:0;" class="text">Cliente: {{ $customer->name }}</h6></td>
            <td><h6 style="margin:0;" class="text">Ciudad: {{ ($customer->city_id) ? '' : '' }}</h6></td>
        </tr>

        <tr>
            <td colspan="2"><h6 style="margin:0;" class="text">Dirección: {{ $customer->address }}</h6></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr class="border-top-bottom">
                <th class="text-center desc-9">CANT.</th>
                <th class="text-left desc-9">CODIGO</th>
                <th class="text-left desc-9">DESCRIPCIÓN</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $row)
            <tr>
                <td class="text-center align-top">
                    @if(((int)$row->quantity != $row->quantity))
                        {{ $row->quantity }}
                    @else
                        {{ number_format($row->quantity, 0) }}
                    @endif
                </td>
                <td class="desc-9 align-top"> {{ $row->item->internal_id }}</td>
                <td class="text-left desc-9 align-top">
                    {!!$row->item->name!!}
                    @if (!empty($row->item->presentation)) {!!$row->item->presentation->description!!} @endif
                    @if($row->attributes)
                        @foreach($row->attributes as $attr)
                            <br>{!! $attr->description !!} : {{ $attr->value }}
                        @endforeach
                    @endif
                    @if($row->discount > 0)
                    <br>{{ $row->discount }}
                    @endif
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <table style="width:100%">
                        <tr>
                            <td class="text-left desc-9">Unit: {{ number_format($row->price, 2)}}</td>
                            <td class="text-left desc-9">Imp: {{ number_format($row->total_tax, 2)}}</td>
                            <td class="text-right desc-9">Tot: {{ number_format($row->subtotal, 2)}}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="3" class="border-bottom"></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <tr>
            <td colspan="2" class="text-right font-bold desc">TOTAL VENTA: $</td>
            <td class="text-right font-bold desc">{{number_format($subtotal, 2)}}</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right font-bold desc">TOTAL DESCUENTO (-): $</td>
            <td class="text-right font-bold desc">{{number_format($descuento, 2)}}</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right font-bold desc">SUBTOTAL: $</td>
            <td class="text-right font-bold desc">{{number_format($total_sin_impuestos, 2)}}</td>
       </tr>
       @foreach ($impuesto as $tax)
        @if ((($tax['total'] > 0) && (!$tax['is_retention'])))
            <tr >
                <td colspan="2" class="text-right font-bold desc">
                    {{$tax['name']}}(+): $
                </td>
                <td class="text-right font-bold desc">{{number_format($tax['total'], 2)}} </td>
            </tr>
        @endif
        @endforeach
        <tr>
            <td colspan="2" class="text-right font-bold desc">TOTAL A PAGAR: $</td>
            <td class="text-right font-bold desc">{{number_format($total_venta, 2)}}</td>
        </tr>
    </table>

    <h6 class="text-center">GRACIAS POR SU COMPRA</h6>
</body>

</html>
