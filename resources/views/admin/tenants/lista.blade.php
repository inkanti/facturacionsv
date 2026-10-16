<div>
    <h1>Negocios (tenants)</h1>

    <div style="margin-bottom:1rem; max-width:340px;">
        <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar por id o nombre... (sin botón, filtra solo)">
    </div>

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

    <div style="margin-top:1rem; display:flex; gap:.5rem;">
        @if($tenants->onFirstPage())
            <span class="btn btn-gris" style="opacity:.5;">← Anterior</span>
        @else
            <button class="btn btn-gris" wire:click="previousPage">← Anterior</button>
        @endif

        <span style="align-self:center; color:#6b7280; font-size:.9rem;">Página {{ $tenants->currentPage() }}</span>

        @if($tenants->hasMorePages())
            <button class="btn btn-gris" wire:click="nextPage">Siguiente →</button>
        @endif
    </div>
</div>