<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante Contable</title>
    <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; position: relative; }
        .header, .footer { width: 100%; }
    .header { border-bottom: 1px solid #ccc; margin-bottom: 18px; padding-bottom: 12px; }
        .footer { border-top: 1px solid #ccc; margin-top: 12px; padding-top: 8px; font-size: 11px; color: #555; }
    .title { font-size: 20px; font-weight: bold; margin-bottom: 6px; }
    .company { font-size: 12px; margin-bottom: 4px; }
        .row { display: table; width: 100%; table-layout: fixed; }
        .col { display: table-cell; vertical-align: top; }
        .col-7 { width: 65%; }
        .col-5 { width: 35%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f0f0f0; }
        .no-border th, .no-border td { border: none; padding: 2px 0; }
        .meta-table td:first-child { width: 35%; font-weight: bold; }
    .logo { height: 50px; margin-right: 10px; margin-bottom: 6px; display: block; }
    .logo-cell { width: 80px; }
        .mt-10 { margin-top: 10px; }
        /* Watermark */
        .watermark {
            position: fixed;
            top: 35%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-20deg);
            font-size: 80px;
            color: rgba(200, 0, 0, 0.12);
            z-index: 0;
            white-space: nowrap;
        }
        .content { position: relative; z-index: 1; }
    </style>
</head>
<body>
    @php
        $estaEliminado = isset($asiento) && method_exists($asiento, 'trashed') ? $asiento->trashed() : false;
        $esAnulado = strtoupper(trim($asiento->estado ?? '')) === 'ANULADO';
    @endphp
    @if($estaEliminado || $esAnulado)
        <div class="watermark">ELIMINADO</div>
    @endif
    <div class="content">
    <div class="header">
        <div class="row">
            <div class="col col-7">
                <table class="no-border" cellspacing="0" cellpadding="0" style="width:100%;">
                    <tr>
                        <td class="logo-cell" style="vertical-align: top;">
                            @php
                                // Logo en public/storage/uploads/logos/logo_{NIT}.jpg
                                $nit = $company->number ?? null;
                                $logoPublicPathJpg = public_path('storage/uploads/logos/logo_' . $nit . '.jpg');
                                $logoPublicPathPng = public_path('storage/uploads/logos/logo_' . $nit . '.png');
                                $logoPath = null;
                                if ($nit) {
                                    if (file_exists($logoPublicPathJpg)) {
                                        $logoPath = $logoPublicPathJpg;
                                    } elseif (file_exists($logoPublicPathPng)) {
                                        $logoPath = $logoPublicPathPng;
                                    }
                                }
                                $mime = null;
                                if ($logoPath) {
                                    $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                                    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                                }
                            @endphp
                            @if(!empty($logoPath))
                                <img class="logo" src="{{ 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath)) }}" alt="Logo" />
                            @endif
                        </td>
                        <td style="vertical-align: top;">
                            <div class="title">Comprobante Contable</div>
                            <div class="company">
                                <strong>{{ $company->name ?? 'Empresa' }}</strong><br>
                                {{ $company->trade_name ?? '' }}<br>
                                NIT: {{ $company->number ?? '' }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col col-5">
                <table class="no-border meta-table" cellspacing="0" cellpadding="0">
                    <tr>
                        <td>Fecha:</td>
                        <td>{{ optional($asiento->fecha_asiento)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td>Número:</td>
                        <td>{{ $asiento->numero_comprobante }}</td>
                    </tr>
                    <tr>
                        <td>Tipo:</td>
                        <td>{{ $asiento->tipoComprobante->nombre ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Estado:</td>
                        <td>{{ $asiento->estado }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Cuerpo: Datos principales -->
    <table class="no-border" style="margin-bottom: 16px;">
        <tr>
            <td><strong>Concepto:</strong> {{ $asiento->concepto }}</td>
        </tr>
    </table>

    <!-- Detalle -->
    <table class="mt-10" style="margin-top: 18px;">
        <thead>
            <tr>
                <th>Cuenta</th>
                <th>Descripción</th>
                <th>Tercero</th>
                <th class="text-right">Débito</th>
                <th class="text-right">Crédito</th>
            </tr>
        </thead>
        <tbody>
            @foreach($asiento->detalles as $d)
                <tr>
                    <td>{{ $d->cuentaContable->codigo ?? '' }}</td>
                    <td>{{ $d->cuentaContable->descripcion ?? '' }}</td>
                    <td>
                        @if($d->tercero)
                            {{ $d->tercero->number }} - {{ $d->tercero->name }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($d->debito, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($d->credito, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Totales</th>
                <th class="text-right">{{ number_format($asiento->total_debito, 2, ',', '.') }}</th>
                <th class="text-right">{{ number_format($asiento->total_credito, 2, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <!-- Firmas -->
    <div style="margin-top: 28px;">
        <table class="no-border" style="width: 100%; text-align: center;">
            <tr>
                <td style="width: 33%; vertical-align: bottom;">
                    <div style="height: 60px;"></div>
                    <div style="border-top: 1px solid #333; margin-top: 8px;"></div>
                    <div style="font-size: 11px; margin-top: 4px;">Elaborado por</div>
                </td>
                <td style="width: 33%; vertical-align: bottom;">
                    <div style="height: 60px;"></div>
                    <div style="border-top: 1px solid #333; margin-top: 8px;"></div>
                    <div style="font-size: 11px; margin-top: 4px;">Revisado por</div>
                </td>
                <td style="width: 33%; vertical-align: bottom;">
                    <div style="height: 60px;"></div>
                    <div style="border-top: 1px solid #333; margin-top: 8px;"></div>
                    <div style="font-size: 11px; margin-top: 4px;">Aprobado por</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        Impreso el {{ now()->format('d/m/Y H:i') }} | Generado por Facturador PRO2
    </div>
    </div>
</body>
</html>
