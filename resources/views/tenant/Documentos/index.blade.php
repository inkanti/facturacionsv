<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Documentos — Facturador SV</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; color: #222; }
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: .45rem; text-align: left; font-size: .9rem; }
        th { background: #f4f4f4; }
        form { margin-bottom: 1rem; }
        input, select, button { padding: .35rem; }
        .badge { padding: .15rem .55rem; border-radius: 10px; font-size: .78rem; }
        .validado { background: #d4edda; } .rechazado { background: #f8d7da; }
        .contingencia { background: #fff3cd; } .borrador { background: #e2e3e5; }
        .firmado, .transmitido { background: #cce5ff; }
    </style>
</head>
<body>
<h1>Documentos</h1>

<form method="get">
    <input type="text" name="buscar" placeholder="N° control o receptor" value="{{ $filtros['buscar'] ?? '' }}">
    <select name="estado">
        <option value="">Todos los estados</option>
        @foreach($estados as $estado)
            <option value="{{ $estado->value }}" @selected(($filtros['estado'] ?? '') === $estado->value)>
                {{ ucfirst($estado->value) }}
            </option>
        @endforeach
    </select>
    <button type="submit">Filtrar</button>
</form>

<table>
    <tr><th>N° control</th><th>Receptor</th><th>Tipo</th><th>Total</th><th>Estado</th><th></th></tr>
    @forelse($documentos as $doc)
        <tr>
            <td>{{ $doc->numero_control ?? '— (borrador)' }}</td>
            <td>{{ $doc->receptor_nombre }}</td>
            <td>{{ $doc->tipo_documento }}</td>
            <td>${{ number_format((float) $doc->total, 2) }}</td>
            <td><span class="badge {{ $doc->estado->value }}">{{ ucfirst($doc->estado->value) }}</span></td>
            <td><a href="{{ route('documentos.show', $doc) }}">Ver</a></td>
        </tr>
    @empty
        <tr><td colspan="6">Sin documentos.</td></tr>
    @endforelse
</table>

{{ $documentos->links() }}
</body>
</html>