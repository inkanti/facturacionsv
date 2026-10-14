<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de suscripción — {{ tenant('name') }}</title>
    <style>
        body { font-family: sans-serif; background: #f3f4f6; display: flex; justify-content: center; padding-top: 4rem; }
        .tarjeta { background: #fff; border-radius: 12px; padding: 2rem; max-width: 480px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,.08); text-align: center; }
        .estado { display: inline-block; padding: .35rem 1rem; border-radius: 999px; font-weight: 600; font-size: .9rem; }
        .ok { background: #dcfce7; color: #166534; }
        .malo { background: #fee2e2; color: #991b1b; }
        .info { color: #6b7280; margin-top: .5rem; }
        .btn { display: inline-block; margin-top: 1.5rem; padding: .6rem 1.4rem; background: #1d4ed8; color: #fff; border-radius: 8px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="tarjeta">
        <h1>Estado de tu suscripción</h1>
        <p class="info">Negocio: <strong>{{ tenant('name') }}</strong></p>

        @if($suscripcion && $suscripcion->estaVigente())
            <p><span class="estado ok">{{ $suscripcion->estado->etiqueta() }}</span></p>
            <p class="info">
                @if($suscripcion->estado->value === 'trial')
                    Tu prueba gratis vence el <strong>{{ $suscripcion->trial_hasta?->format('d/m/Y') }}</strong>.
                @else
                    Plan <strong>{{ $suscripcion->plan?->nombre ?? '—' }}</strong>, vigente hasta el
                    <strong>{{ $suscripcion->vence_en?->format('d/m/Y') }}</strong>.
                @endif
            </p>
            <a class="btn" href="{{ route('tenant.welcome') }}">Volver al inicio</a>
        @else
            <p><span class="estado malo">
                {{ $suscripcion ? $suscripcion->estado->etiqueta() : 'Sin suscripción' }}
            </span></p>
            <p class="info">
                Tu acceso está temporalmente restringido.
                @if($suscripcion && $suscripcion->trial_hasta)
                    La prueba gratis venció el {{ $suscripcion->trial_hasta->format('d/m/Y') }}.
                @endif
                <br>Contacta al administrador de FACTURADOR SV para reactivar tu plan.
            </p>
        @endif
    </div>
</body>
</html>