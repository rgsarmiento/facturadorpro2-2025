<div style="margin-top:10px; margin-bottom:10px;">
    <table>
        <tr>
            <td width="80%">
                <p><strong>Empresa: </strong>{{$company->name}}</p>
                <p><strong>Establecimiento: </strong>{{$establishment->description}}</p>
                <p><strong>Teléfono: </strong>{{$establishment->telephone}} - <strong>Email: </strong>{{$establishment->email}}</p>
                <p><strong>Dirección: </strong>{{$establishment->address}}</p>
            </td>
            <td width="45%; text-align: right;" class="vertical-align-top">
                @if($company->logo)
                    {!! render_company_logo($company, 'company_logo', 'width: 50%; margin-right:50px;') !!}
                @endif
            </td>
        </tr>
    </table>
</div>
