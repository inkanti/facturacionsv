<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        .header { width: 100%; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; }
        .header td { vertical-align: top; }
        .right { text-align: right; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        table.detalle { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .detalle th, .detalle td { border: 1px solid #999; padding: 4px; }
        .detalle th { background: #eee; }
        .totales { width: 40%; margin-left: auto; margin-top: 8px; }
        .totales td { padding: 3px; }
        .sello { font-size: 9px; word-break: break-all; margin-top: 12px; color: #555; }
        .qr { width: 110px; }
    </style>
</head>
<body>
@php
    $json = $documento->json_dte ?? [];
    $emisorData = $json['emisor'] ?? [];
    $resp = $documento->respuesta_mh['raw'] ?? [];
@endphp

<table class="header">
    <tr>
        <td>
            <h1>{{ $emisorData['nombre'] ?? 'Emisor' }}</h1>
            NIT: {{ $emisorData['nit'] ?? '—' }} · NRC: {{ $emisorData['nrc'] ?? '—' }}<br>
            {{ $emisorData['direccion']['complemento'] ?? '' }}<br>
            Tel: {{ $emisorData['telefono'] ?? '—' }} · {{ $emisorData['correo'] ?? '' }}
        </td>
        <td class="right">
            <strong>{{ $json['identificacion']['tipoDte'] === '03' ? 'COMPROBANTE DE CRÉDITO FISCAL' : 'DOCUMENTO TRIBUTARIO ELECTRÓNICO' }}</strong><br>
            N° Control: {{ $documento->numero_control }}<br>
            Código Generación: {{ $documento->codigo_generacion }}<br>
            Fecha: {{ optional($documento->fecha_emision)->format('d/m/Y H:i') }}
        </td>
    </tr>
</table>

<p>
    <strong>Receptor:</strong> {{ $documento->receptor_nombre }}
    · NIT: {{ $documento->receptor_num_documento ?? 'CF' }}
    · NRC: {{ $json['receptor']['nrc'] ?? '—' }}
</p>

<table class="detalle">
    <tr><th>#</th><th>Descripción</th><th>Cant.</th><th>P. Unit.</th><th>Gravada</th><th>Exenta</th></tr>
    @foreach($json['cuerpoDocumento'] ?? [] as $item)
        <tr>
            <td>{{ $item['numItem'] }}</td>
            <td>{{ $item['descripcion'] }}</td>
            <td>{{ $item['cantidad'] }}</td>
            <td>${{ number_format($item['precioUni'], 2) }}</td>
            <td>${{ number_format($item['ventaGravada'], 2) }}</td>
            <td>${{ number_format($item['ventaExenta'], 2) }}</td>
        </tr>
    @endforeach
</table>

<table class="totales">
    <tr><td>Subtotal:</td><td class="right">${{ number_format((float) $documento->subtotal, 2) }}</td></tr>
    <tr><td>IVA (13%):</td><td class="right">${{ number_format((float) $documento->iva, 2) }}</td></tr>
    <tr><td><strong>Total a pagar:</strong></td><td class="right"><strong>${{ number_format((float) $documento->total, 2) }}</strong></td></tr>
</table>
<p><em>{{ $json['resumen']['totalLetras'] ?? '' }}</em></p>

<table style="width:100%; margin-top:20px;">
    <tr>
        <td>
            @if($resp['selloRecibido'] ?? null)
                <div class="sello">
                    Sello de recepción MH:<br>{{ $resp['selloRecibido'] }}<br>
                    Procesado: {{ $resp['fhProcesamiento'] ?? '—' }}
                </div>
            @endif
        </td>
        <td class="right"><img class="qr" src="{{ $qr }}"></td>
    </tr>
</table>
</body>
</html>