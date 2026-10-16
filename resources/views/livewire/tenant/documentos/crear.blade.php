<div>
    <h1>Nuevo documento ({{ $tipo_documento === '01' ? 'Factura 01' : 'CCF 03' }})</h1>

    
    @if($errors->any())
        <div style="background:#fee2e2; color:#991b1b; padding:.7rem 1rem; border-radius:8px; margin-bottom:1rem;">{{ $errors->first() }}</div>
    @endif

    <div class="tarjeta">
<!--<label>Receptor (cliente)</label><br>  -->
       

<div class="campo">
    <label for="tipo_documento">Tipo de documento</label>
    <select id="tipo_documento" wire:model.live="tipo_documento">
        <option value="03">CCF (03) — Crédito fiscal (cliente con NRC)</option>
        <option value="01">Factura (01) — Consumidor final</option>
    </select>
    @error('tipo_documento') <p class="error">{{ $message }}</p> @enderror
</div>

        <select wire:model="receptor_id" style="max-width:480px;">
            <option value="">— Selecciona un receptor —</option>
            @foreach($receptores as $receptor)
                <option value="{{ $receptor->id }}">{{ $receptor->nombre }} — {{ $receptor->nit }}</option>
            @endforeach
        </select>
        @error('receptor_id') <div style="color:#991b1b; font-size:.85rem;">{{ $message }}</div> @enderror
    </div>

    <div class="tarjeta">
        <strong>Ítems</strong>
        <table style="margin-top:.8rem;">
            <thead>
            <tr><th style="width:45%;">Producto / servicio</th><th>Cantidad</th><th>Precio unit.</th><th>IVA</th><th>Subtotal</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($items as $i => $item)
                @php($producto = $productos->firstWhere('id', $item['producto_id']))
                <tr>
                    <td>
                        <select wire:model.live="items.{{ $i }}.producto_id" style="width:100%;">
                            <option value="">— Selecciona —</option>
                            @foreach($productos as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" wire:model.live.debounce.300ms="items.{{ $i }}.cantidad" min="0.01" step="0.01" style="width:90px;"></td>
                    <td>${{ number_format((float) ($producto?->precio_unitario ?? 0), 2) }}</td>
                    <td>{{ $producto ? $producto->tipo_iva->porcentaje() . '%' : '—' }}</td>
                    <td>${{ number_format((float) ($producto?->precio_unitario ?? 0) * (float) $item['cantidad'], 2) }}</td>
                    <td>
                        @if(count($items) > 1)
                            <button class="btn btn-gris" wire:click="quitarItem({{ $i }})" title="Quitar">✕</button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <button class="btn btn-gris" wire:click="agregarItem">+ Agregar ítem</button>
    </div>

    <div class="tarjeta" style="max-width:360px; margin-left:auto;">
        <div style="display:flex; justify-content:space-between; padding:.25rem 0;"><span>Subtotal</span><strong>${{ number_format($totales['subtotal'], 2) }}</strong></div>
        <div style="display:flex; justify-content:space-between; padding:.25rem 0;"><span>IVA</span><strong>${{ number_format($totales['iva'], 2) }}</strong></div>
        <hr style="border:0; border-top:1px solid #e5e7eb; margin:.4rem 0;">
        <div style="display:flex; justify-content:space-between; padding:.25rem 0; font-size:1.15rem;"><span>Total</span><strong>${{ number_format($totales['total'], 2) }}</strong></div>
    </div>

    <div style="display:flex; gap:.5rem; margin-top:1rem;">
        <button class="btn btn-azul" wire:click="emitir" wire:confirm="¿Emitir este documento? Se firmará y transmitirá.">Emitir documento</button>
        <a class="btn btn-gris" href="{{ route('documentos.index') }}">Cancelar</a>
    </div>
</div>