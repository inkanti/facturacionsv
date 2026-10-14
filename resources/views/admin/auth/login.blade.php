<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — FACTURADOR SV</title>
    <style>
        body { font-family: sans-serif; background: #111827; display: flex; justify-content: center; padding-top: 6rem; }
        .caja { background: #fff; border-radius: 12px; padding: 2rem; width: 360px; }
        h1 { font-size: 1.2rem; margin-bottom: 1.2rem; }
        label { display: block; margin: .8rem 0 .25rem; font-size: .88rem; font-weight: 600; }
        input { width: 100%; padding: .55rem; border: 1px solid #d1d5db; border-radius: 7px; }
        button { margin-top: 1.3rem; width: 100%; padding: .6rem; background: #1d4ed8; color: #fff; border: 0; border-radius: 7px; cursor: pointer; font-size: 1rem; }
        .error { background: #fee2e2; color: #991b1b; padding: .6rem .8rem; border-radius: 7px; margin-bottom: 1rem; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="caja">
        <h1>Administración FACTURADOR SV</h1>
        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <label>Correo</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            <label>Contraseña</label>
            <input type="password" name="password" required>
            <button type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>