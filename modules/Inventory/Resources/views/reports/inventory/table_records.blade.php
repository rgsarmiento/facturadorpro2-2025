
<div class="">
    <div class=" ">
        <table class="">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Inventario actual</th>
                    <th>Precio de venta</th>
                    <th>Costo</th>
                    <th>Almacén</th>

                    <th class="text-right">
                        Precio de venta Global
                    </th>
                    <th class="text-right">
                        Costo Global
                    </th>
                </tr>
            </thead>
            <tbody>

                @php
                    $total_global_sale_unit_price = 0;
                    $total_global_purchase_unit_price = 0;
                @endphp

                @foreach($records as $key => $value)

                    @php
                        // Calcular stock según la fecha seleccionada (si existe)
                        $stock = isset($date) && !empty($date) ? $value->getStockByDate($date) : $value->stock;

                        // Calcular precios globales con el stock correcto
                        $global_sale_unit_price = number_format($value->item->sale_unit_price * $stock, 6, ".", "");
                        $global_purchase_unit_price = number_format($value->item->purchase_unit_price * $stock, 6, ".", "");

                        $total_global_sale_unit_price += $global_sale_unit_price;
                        $total_global_purchase_unit_price += $global_purchase_unit_price;
                    @endphp
                    <tr>
                        <td class="celda">{{$loop->iteration}}</td>
                        <td class="celda">{{$value->item->internal_id}}</td>
                        <td class="celda">{{$value->item->name ?? ''}}</td>
                        <td class="celda">{{$stock}}</td>
                        <td class="celda">{{$value->item->sale_unit_price}}</td>
                        <td class="celda">{{$value->item->purchase_unit_price}}</td>
                        <td class="celda">{{$value->warehouse->description}}</td>

                        <td class="celda text-right">{{$global_sale_unit_price}}</td>
                        <td class="celda text-right">{{$global_purchase_unit_price}}</td>
                    </tr>
                @endforeach

                <tr>
                    <td class="celda" colspan="5"></td>
                    <td class="celda">Total</td>
                    <td class="celda text-right">{{ number_format($total_global_sale_unit_price, 6, ".", "") }}</td>
                    <td class="celda text-right">{{ number_format($total_global_purchase_unit_price, 6, ".", "") }}</td>
                </tr>

            </tbody>
        </table>
    </div>
</div>
