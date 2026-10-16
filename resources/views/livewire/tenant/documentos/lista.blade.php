<div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
            <h1 style="margin:0;">Documentos</h1>
            <a class="btn btn-azul" href="{{ route('documentos.crear') }}">+ Nuevo documento</a>
        </div>

    <div class="fila-filtros">
        <div>
            <label>N° control o receptor</label><br>
            <input type="text" wire:model.live.debounce.300ms="buscar" placeholder="Buscar...">
        </div>
        <div>
            <label>Estado</label><br>
            <select wire:model.live="estado">
                <option value="">Todos los estados</option>
                <option value="borrador">Borrador</option>
                <option value="firmado">Firmado</option>
                <option value="transmitido">Transmitido</option>
                <option value="validado">Validado</option>
                <option value="rechazado">Rechazado</option>
                <option value="contingencia">Contingencia</option>
            </select>
        </div>
    </div>

    <table>
        <thead>
        <tr><th>N° control</th><th>Receptor</th><th>Tipo</th><th>Total</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
        @forelse($documentos as $documento)
            <tr>
                <td>{{ $documento->numero_control ?? '—' }}</td>
                <td>{{ $documento->receptor_nombre }}</td>
                <td>{{ $documento->tipo_documento }}</td>
                <td>${{ number_format((float) $documento->total, 2) }}</td>
                <td><span class="badge b-{{ $documento->estado->value }}">{{ ucfirst($documento->estado->value) }}</span></td>
                <td><a class="btn btn-gris" href="{{ route('documentos.show', $documento) }}">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center; color:#6b7280;">Sin documentos.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div style="display:flex; gap:.5rem;">
        @if($documentos->onFirstPage())
            <span class="btn btn-gris" style="opacity:.5;">← Anterior</span>
        @else
            <button class="btn btn-gris" wire:click="previousPage">← Anterior</button>
        @endif
        <span style="align-self:center; color:#6b7280; font-size:.9rem;">Página {{ $documentos->currentPage() }}</span>
        @if($documentos->hasMorePages())
            <button class="btn btn-gris" wire:click="nextPage">Siguiente →</button>
        @endif
    </div>
</div>