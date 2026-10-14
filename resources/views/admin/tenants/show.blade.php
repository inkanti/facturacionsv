@extends('admin.layout')
@section('titulo', 'Tenant ' . $tenant->id)
@section('contenido')
    <h1>Tenant: {{ $tenant->name }} <small style="color:#6b7280;">({{ $tenant->id }})</small></h1>
    <p style="margin-bottom:1.5rem; color:#6b7280;">
        Dominios: {{ $tenant->domains->pluck('domain')->implode(', ') }} ·
        Registrado: {{ $tenant->created_at?->format('d/m/Y') }}
    </p>

    <h1 style="font-size:1.1rem;">Suscripción actual</h1>
    @if($suscripcionActual)
        <table style="max-width:640px;">
            <tbody>
            <tr><th style="width:170px;">Estado</th>
                <td><span class="badge b-{{ $suscripcionActual->estado->value }}">{{ $suscripcionActual->estado->etiqueta() }}</span>
                    @if(! $suscripcionActual->estaVigente()) <span style="color:#991b1b;">(sin acceso)</span> @endif</td></tr>
            <tr><th>Plan</th><td>{{ $suscripcionActual->plan?->nombre ?? '—' }}</td></tr>
            <tr><th>Inicia</th><td>{{ $suscripcionActual->inicia_en?->format('d/m/Y') ?? '—' }}</td></tr>
            <tr><th>Vence</th><td>{{ $suscripcionActual->vence_en?->format('d/m/Y') ?? '—' }}</td></tr>
            <tr><th>Prueba hasta</th><td>{{ $suscripcionActual->trial_hasta?->format('d/m/Y') ?? '—' }}</td></tr>
            </tbody>
        </table>
    @else
        <p style="margin-bottom:1rem;">Este tenant no tiene suscripción.</p>
    @endif

    <div class="tarjetas" style="margin-top:1.5rem;">
        <div class="tarjeta">
            <strong>Asignar / cambiar plan</strong>
            <form method="POST" action="{{ route('admin.tenants.asignar-plan', $tenant) }}">
                @csrf
                <label>Plan</label>
                <select name="plan_id">
                    @foreach($planes as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->nombre }} — ${{ $plan->precio_mensual }}/mes</option>
                    @endforeach
                </select>
                <label>Meses</label>
                <input type="number" name="meses" value="1" min="1" max="12">
                <div style="margin-top:1rem;"><button class="btn btn-azul">Asignar plan</button></div>
            </form>
        </div>

        <div class="tarjeta">
            <strong>Otras acciones</strong>
            @if($suscripcionActual && $suscripcionActual->estado->value !== 'trial')
                <form method="POST" action="{{ route('admin.tenants.extender', $tenant) }}" style="display:flex; gap:.5rem; align-items:end; margin-top:1rem;">
                    @csrf
                    <div><label>Extender (meses)</label><input type="number" name="meses" value="1" min="1" max="12" style="width:90px;"></div>
                    <button class="btn btn-azul">Extender</button>
                </form>
                @if($suscripcionActual->estaVigente())
                    <form class="inline" method="POST" action="{{ route('admin.tenants.suspender', $tenant) }}" style="margin-top:1rem;">
                        @csrf
                        <button class="btn btn-rojo" onclick="return confirm('¿Suspender el acceso de este negocio?')">Suspender</button>
                    </form>
                @else
                    <form class="inline" method="POST" action="{{ route('admin.tenants.reactivar', $tenant) }}" style="margin-top:1rem;">
                        @csrf
                        <button class="btn btn-azul">Reactivar</button>
                    </form>
                @endif
            @elseif($suscripcionActual)
                <p style="margin-top:1rem; color:#6b7280;">Está en prueba gratis; al asignar un plan la prueba se cierra.</p>
            @else
                <form method="POST" action="{{ route('admin.tenants.iniciar-trial', $tenant) }}" style="margin-top:1rem;">
                    @csrf
                    <button class="btn btn-azul">Iniciar prueba gratis (14 días)</button>
                </form>
            @endif
        </div>
    </div>

    <h1 style="font-size:1.1rem; margin-top:2rem;">Historial de suscripciones</h1>
    <table style="max-width:760px;">
        <thead><tr><th>#</th><th>Estado</th><th>Plan</th><th>Inicia</th><th>Vence</th><th>Prueba hasta</th></tr></thead>
        <tbody>
        @foreach($suscripciones as $sus)
            <tr>
                <td>{{ $sus->id }}</td>
                <td><span class="badge b-{{ $sus->estado->value }}">{{ $sus->estado->etiqueta() }}</span></td>
                <td>{{ $sus->plan?->nombre ?? '—' }}</td>
                <td>{{ $sus->inicia_en?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $sus->vence_en?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $sus->trial_hasta?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection