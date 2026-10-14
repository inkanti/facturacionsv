<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Admin') — FACTURADOR SV</title>
    <style>
        * { box-sizing: border-box; margin: 0; }
        body { font-family: sans-serif; background: #f3f4f6; color: #111827; }
        nav { background: #111827; color: #fff; padding: .8rem 1.5rem; display: flex; gap: 1.5rem; align-items: center; }
        nav a { color: #d1d5db; text-decoration: none; font-size: .95rem; }
        nav a:hover { color: #fff; }
        nav .marca { font-weight: 700; color: #fff; }
        nav form { margin-left: auto; }
        nav button { background: #dc2626; color: #fff; border: 0; padding: .4rem .9rem; border-radius: 6px; cursor: pointer; }
        main { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.4rem; margin-bottom: 1.2rem; }
        .tarjetas { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .tarjeta { background: #fff; border-radius: 10px; padding: 1.2rem; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .tarjeta .num { font-size: 1.8rem; font-weight: 700; }
        .tarjeta .lbl { color: #6b7280; font-size: .85rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); margin-bottom: 1rem; }
        th, td { text-align: left; padding: .65rem .9rem; border-bottom: 1px solid #e5e7eb; font-size: .92rem; }
        th { background: #f9fafb; }
        .exito { background: #dcfce7; color: #166534; padding: .7rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .error { background: #fee2e2; color: #991b1b; padding: .7rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .badge { display: inline-block; padding: .15rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 600; }
        .b-activa, .b-trial { background: #dcfce7; color: #166534; }
        .b-vencida, .b-suspendida, .b-cancelada { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; padding: .45rem 1rem; border-radius: 7px; border: 0; cursor: pointer; font-size: .9rem; text-decoration: none; }
        .btn-azul { background: #1d4ed8; color: #fff; }
        .btn-gris { background: #e5e7eb; color: #111827; }
        .btn-rojo { background: #dc2626; color: #fff; }
        form.inline { display: inline; }
        input, select, textarea { padding: .5rem; border: 1px solid #d1d5db; border-radius: 7px; font-size: .92rem; width: 100%; }
        label { display: block; margin: .8rem 0 .25rem; font-size: .88rem; font-weight: 600; }
    </style>
</head>
<body>
<nav>
    <span class="marca">FACTURADOR SV</span>
    <a href="{{ route('admin.dashboard') }}">Panel</a>
    <a href="{{ route('admin.tenants.index') }}">Tenants</a>
    <a href="{{ route('admin.planes.index') }}">Planes</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Salir ({{ Auth::guard('admin')->user()?->nombre }})</button>
    </form>
</nav>
<main>
    @if(session('exito'))
        <div class="exito">{{ session('exito') }}</div>
    @endif
    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif
    @yield('contenido')
</main>
</body>
</html>