<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — {{ tenant('name') ?? tenant('id') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-lg shadow p-8 w-full max-w-sm">
        <h1 class="text-xl font-bold mb-1">{{ tenant('name') ?? 'Mi negocio' }}</h1>
        <p class="text-sm text-gray-500 mb-6">Iniciá sesión para continuar</p>

        @if ($errors->any())
            <div class="bg-red-50 text-red-700 text-sm rounded p-3 mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Correo</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Contraseña</label>
                <input type="password" name="password" required
                       class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember"> Recordarme
            </label>
            <button type="submit"
                    class="w-full bg-indigo-600 text-white rounded py-2 text-sm font-medium hover:bg-indigo-700">
                Iniciar sesión
            </button>
        </form>
    </div>
</body>
</html>