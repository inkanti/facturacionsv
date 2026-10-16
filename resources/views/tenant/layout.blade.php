<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Facturador') — {{ tenant('name') }}</title>
    @livewireStyles
    <style>
        * { box-sizing: border-box; margin: 0; }
        body { font-family: sans-serif; background: #f3f4f6; color: #111827; }
        nav { background: #1e3a8a; color: #fff; padding: .8rem 1.5rem; display: flex; gap: 1.5rem; align-items: center; }
        nav a { color: #dbeafe; text-decoration: none; font-size: .95rem; }
        nav a:hover { color: #fff; }
        nav .marca { font-weight: 700; color: #fff; }
        main { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.4rem; margin-bottom: 1.2rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); margin-bottom: 1rem; }
        th, td { text-align: left; padding: .65rem .9rem; border-bottom: 1px solid #e5e7eb; font-size: .92rem; }
        th { background: #f9fafb; }
        .badge { display: inline-block; padding: .15rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 600; }
        .b-validado { background: #dcfce7; color: #166534; }
        .b-borrador { background: #e5e7eb; color: #374151; }
        .b-firmado, .b-transmitido { background: #dbeafe; color: #1e40af; }
        .b-rechazado { background: #fee2e2; color: #991b1b; }
        .b-contingencia { background: #fef9c3; color: #854d0e; }
        .btn { display: inline-block; padding: .45rem 1rem; border-radius: 7px; border: 0; cursor: pointer; font-size: .9rem; text-decoration: none; }
        .btn-azul { background: #1d4ed8; color: #fff; }
        .btn-gris { background: #e5e7eb; color: #111827; }
        input, select { padding: .5rem; border: 1px solid #d1d5db; border-radius: 7px; font-size: .92rem; }
        label { font-size: .88rem; font-weight: 600; }
        .fila-filtros { display: flex; gap: .6rem; align-items: end; margin-bottom: 1rem; }
        .tarjeta { background: #fff; border-radius: 10px; padding: 1.2rem; box-shadow: 0 1px 4px rgba(0,0,0,.07); margin-bottom: 1rem; }
        .fila-datos { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .8rem; }
        .fila-datos .lbl { color: #6b7280; font-size: .8rem; }
        .fila-datos .val { font-weight: 600; }
    </style>
</head>
<body>
<nav>
    <span class="marca">{{ tenant('name') }}</span>
    <a href="{{ route('tenant.welcome') }}">Inicio</a>
    <a href="{{ route('documentos.index') }}">Documentos</a>
    <a href="{{ route('suscripcion.estado') }}">Mi suscripción</a>
</nav>
<main>
    @yield('contenido')
    {{ $slot ?? '' }}
</main>
@livewireScripts
</body>
</html>