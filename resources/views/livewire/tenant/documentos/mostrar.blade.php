<div>
    <h1>Documento {{ $documento->numero_control ?? ('#' . $documento->id) }}</h1>

    <div class="tarjeta">
        <div class="fila-datos">
            <div><div class="lbl">Estado</div><div class="val">
                <span class="badge b-{{ $documento->estado->value }}">{{ ucfirst($documento->estado->value) }}</span>
            </div></div>
            <div><div class="lbl">Tipo (CAT-002)</div><div class="val">{{ $documento->tipo_documento }}</div></div>
            <div><div class="lbl">Fecha</div><div class="val">{{ $documento->created_at?->format('d/m/Y H:i') }}</div></div>
            <div><div class="lbl">Código generación</div><div class="val" style="font-size:.8rem;">{{ $documento->codigo_generacion ?? '—' }}</div></div>
        </div>
        <hr style="margin:1rem 0; border:0; border-top:1px solid #e5e7eb;">
        <div class="fila-datos">
            <div><div class="lbl">Receptor</div><div class="val">{{ $documento->receptor_nombre }}</div></div>
            <div><div class="lbl">NIT receptor</div><div class="val">{{ $documento->receptor_nit }}</div></div>
            <div><div class="lbl">Subtotal</div><div class="val">${{ number_format((float) $documento->subtotal, 2) }}</div></div>
            <div><div class="lbl">IVA</div><div class="val">${{ number_format((float) $documento->iva, 2) }}</div></div>
            <div><div class="lbl">Total</div><div class="val">${{ number_format((float) $documento->total, 2) }}</div></div>
        </div>
    </div>

    {{-- Si es una nota (05/06), mostrar el documento que afecta --}}
    @if($documento->documento_padre_id && $documento->padre)
        <div class="tarjeta">
            <strong>Documento afectado</strong><br>
            <a href="{{ route('documentos.show', $documento->padre) }}" style="color:#1d4ed8;">
                {{ $documento->padre->numero_control }}
            </a>
            · {{ $documento->padre->receptor_nombre }}
        </div>
    @endif

    <div style="display:flex; gap:.5rem; margin-bottom:1rem; flex-wrap:wrap;">
        <a class="btn btn-gris" href="{{ route('documentos.json', $documento) }}">Descargar JSON</a>
        <a class="btn btn-gris" href="{{ route('documentos.pdf', $documento) }}">Descargar PDF</a>
        <form method="POST" action="{{ route('documentos.consultar-mh', $documento) }}">
            @csrf
            <button class="btn btn-azul" type="submit">Consultar en MH</button>
        </form>

        {{-- Notas sobre CCF validado --}}
        @if($documento->estado->value === 'validado' && $documento->tipo_documento === '03')
            <a class="btn btn-gris" href="{{ route('documentos.notas.crear', ['documento' => $documento, 'tipo' => '05']) }}">Crear nota de crédito</a>
            <a class="btn btn-gris" href="{{ route('documentos.notas.crear', ['documento' => $documento, 'tipo' => '06']) }}">Crear nota de débito</a>
        @endif
    </div>

    {{-- Notas emitidas sobre este documento --}}
    @if($documento->notas->count() > 0)
        <div class="tarjeta">
            <strong>Notas emitidas sobre este documento</strong>
            <ul style="margin-top:.5rem; padding-left:1.2rem;">
                @foreach($documento->notas as $nota)
                    <li>
                        <a href="{{ route('documentos.show', $nota) }}" style="color:#1d4ed8;">{{ $nota->numero_control ?? ('#' . $nota->id) }}</a>
                        ({{ $nota->tipo_documento === '05' ? 'NC' : 'ND' }})
                        — <span class="badge b-{{ $nota->estado->value }}">{{ ucfirst($nota->estado->value) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('exito'))
        <div style="background:#dcfce7; color:#166534; padding:.7rem 1rem; border-radius:8px; margin-bottom:1rem;">{{ session('exito') }}</div>
    @endif

    @if($documento->respuesta_mh)
        <div class="tarjeta">
            <strong>Respuesta del MH</strong>
            @if(is_array($documento->respuesta_mh))
                <p style="margin-top:.5rem;">Sello: {{ $documento->respuesta_mh['selloRecibido'] ?? '—' }}</p>
                <p>Código: {{ $documento->respuesta_mh['codigoMsg'] ?? '—' }} — {{ $documento->respuesta_mh['descripcionMsg'] ?? '' }}</p>
            @else
                <pre style="margin-top:.5rem; white-space:pre-wrap;">{{ $documento->respuesta_mh }}</pre>
            @endif
        </div>
    @endif

    <a href="{{ route('documentos.index') }}" style="color:#1d4ed8;">← Volver al listado</a>
</div>