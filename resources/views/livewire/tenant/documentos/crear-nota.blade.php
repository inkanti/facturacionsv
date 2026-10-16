<div>
    <h1>{{ $tipo === '05' ? 'Nota de crédito (05)' : 'Nota de débito (06)' }}</h1>

    @if($errors->any())
        <div style="background:#fee2e2; color:#991b1b; padding:.7rem 1rem; border-radius:8px; margin-bottom:1rem;">{{ $errors->first() }}</div>
    @endif

    <div class="tarjeta">
        <strong>Documento afectado</strong><br>
        N° Control: {{ $padre->numero_control }}<br>
        Receptor: {{ $padre->receptor_nombre }}<br>
        Fecha: {{ optional($padre->fecha_emision)->format('d/m/Y') }}
    </div>

    <div class="tarjeta">
        <strong>Ítems (ajuste las cantidades)</strong>
        <table style="margin-top:.8rem;">
            <thead>
            <tr><th style="width:50%;">Descripción</th><th>Cantidad</th><th>Precio unit.</th><th>IVA</th><th>Subtotal</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($items as $i => $item)
                @php($detalle = $detallesPadre->get($item['detalle_id']))
                <tr>
                    <td>{{ $detalle?->nombre }}</td>
                    <td><input type="number" wire:model.live.debounce.300ms="items.{{ $i }}.cantidad" min="0.01" step="0.01" style="width:90px;"></td>
                    <td>${{ number_format((float) ($detalle?->precio_unitario ?? 0), 2) }}</td>
                    <td>{{ $detalle ? $detalle->tipo_iva->porcentaje() . '%' : '—' }}</td>
                    <td>${{ number_format((float) ($detalle?->precio_unitario ?? 0) * (float) $item['cantidad'], 2) }}</td>
                    <td>
                        @if(count($items) > 1)
                            <button class="btn btn-gris" wire:click="quitarItem({{ $i }})" title="Quitar">✕</button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="tarjeta" style="max-width:360px; margin-left:auto;">
        <div style="display:flex; justify-content:space-between; padding:.25rem 0;"><span>Subtotal</span><strong>${{ number_format($totales['subtotal'], 2) }}</strong></div>
        <div style="display:flex; justify-content:space-between; padding:.25rem 0;"><span>IVA</span><strong>${{ number_format($totales['iva'], 2) }}</strong></div>
        <hr style="border:0; border-top:1px solid #e5e7eb; margin:.4rem 0;">
        <div style="display:flex; justify-content:space-between; padding:.25rem 0; font-size:1.15rem;"><span>Total</span><strong>${{ number_format($totales['total'], 2) }}</strong></div>
    </div>

    <div style="display:flex; gap:.5rem; margin-top:1rem;">
        <button class="btn btn-azul" wire:click="emitirNota" wire:confirm="¿Emitir esta nota? Se firmará y transmitirá.">Emitir nota</button>
        <a class="btn btn-gris" href="{{ route('documentos.show', $padre) }}">Cancelar</a>
    </div>
</div>