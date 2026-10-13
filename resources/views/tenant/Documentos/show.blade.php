<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Documento {{ $documento->numero_control }}</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; color: #222; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { border: 1px solid #ccc; padding: .4rem; text-align: left; font-size: .9rem; }
        th { background: #f4f4f4; }
        .acciones a, .acciones button { margin-right: .5rem; }
        pre { background: #f8f8f8; padding: 1rem; overflow: auto; font-size: .8rem; }
        .ok { color: #1a7f37; } .error { color: #b02a37; }
    </style>
</head>
<body>
<p><a href="{{ route('documentos.index') }}">&larr; Volver al listado</a></p>

<h1>{{ $documento->numero_control ?? 'Borrador' }}</h1>
<p>
    Estado: <strong>{{ ucfirst($documento->estado->value) }}</strong> ·
    Código de generación: {{ $documento->codigo_generacion ?? '—' }} ·
    Fecha: {{ optional($documento->fecha_emision)->format('d/m/Y H:i') ?? '—' }}
</p>

<div class="acciones">
    <a href="{{ route('documentos.json', $documento) }}">Descargar JSON</a>
    <a href="{{ route('documentos.pdf', $documento) }}">Descargar PDF</a>
    @if($documento->codigo_generacion)
        <form method="post" action="{{ route('documentos.consultar', $documento) }}" style="display:inline">
            @csrf
            <button type="submit">Consultar en MH</button>
        </form>
    @endif
</div>

@if(session('consulta_mh'))
    <h3>Respuesta del MH</h3>
    <pre>{{ json_encode(session('consulta_mh'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
@endif

<h3>Receptor</h3>
<p>{{ $documento->receptor_nombre }} ({{ $documento->receptor_num_documento ?? 'CF' }})</p>

<h3>Detalle</h3>
<table>
    <tr><th>Código</th><th>Descripción</th><th>Cant.</th><th>P. Unit.</th><th>IVA</th><th>Subtotal</th></tr>
    @foreach($documento->detalles as $item)
        <tr>
            <td>{{ $item->codigo }}</td>
            <td>{{ $item->nombre }}</td>
            <td>{{ $item->cantidad }}</td>
            <td>${{ number_format((float) $item->precio_unitario, 2) }}</td>
            <td>${{ number_format((float) $item->iva, 2) }}</td>
            <td>${{ number_format((float) $item->subtotal, 2) }}</td>
        </tr>
    @endforeach
    <tr>
        <th colspan="5" style="text-align:right">Subtotal / IVA / Total</th>
        <th>${{ number_format((float) $documento->total, 2) }}</th>
    </tr>
</table>

<h3>Respuesta MH almacenada</h3>
<pre>{{ json_encode($documento->respuesta_mh, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</body>
</html>