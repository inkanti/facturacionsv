<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $tenant->name }} — FACTURADOR SV</title>
</head>
<body>
    {{-- Vista temporal solo para verificar que el tenant se resuelve --}}
    <h1>{{ $tenant->name }}</h1>
    <p>Base de datos activa: <code>{{ $database }}</code></p>
</body>
</html>