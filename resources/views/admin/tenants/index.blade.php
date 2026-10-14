@extends('admin.layout')
@section('titulo', 'Tenants')
@section('contenido')
    <h1>Negocios (tenants)</h1>
    <form method="GET" style="margin-bottom:1rem; max-width:340px; display:flex; gap:.5rem;">
        <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Buscar por id o nombre...">
        <button class="btn btn-azul">Buscar</button>
    </form>
    <table>
        <thead>
        <tr><th>ID</th><th>Nombre</th><th>Dominios</th><th>Plan</th><th>Estado</th><th>Vence</th><th></th></tr>
        </thead>
        <tbody>
        @foreach($tenants as $tenant)
            @php($sus = $suscripciones->get($tenant->id)?->sortByDesc('id')->first())
            <tr>
                <td>{{ $tenant->id }}</td>
                <td>{{ $tenant->name }}</td>
                <td>{{ $tenant->domains->pluck('domain')->implode(', ') }}</td>
                <td>{{ $sus?->plan?->nombre ?? ($sus?->estado?->value === 'trial' ? 'Prueba gratis' : '—') }}</td>
                <td>
                    @if($sus)
                        <span class="badge b-{{ $sus->estado->value }}">{{ $sus->estado->etiqueta() }}</span>
                    @else
                        <span class="badge b-vencida">Sin suscripción</span>
                    @endif
                </td>
                <td>{{ $sus?->vence_en?->format('d/m/Y') ?? $sus?->trial_hasta?->format('d/m/Y') ?? '—' }}</td>
                <td><a class="btn btn-gris" href="{{ route('admin.tenants.show', $tenant) }}">Gestionar</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $tenants->links() }}
@endsection