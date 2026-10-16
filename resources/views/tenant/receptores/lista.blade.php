{{-- Módulo 11 — CRUD de receptores (clientes), versión final con catálogos MH --}}
<div class="max-w-6xl mx-auto p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Clientes</h1>
        <a href="{{ url('/documentos') }}" class="text-sm text-blue-600 hover:underline">&larr; Volver a documentos</a>
    </div>

    @if (session('ok'))
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-2 rounded">{{ session('ok') }}</div>
    @endif

    {{-- Formulario crear/editar --}}
    <div class="bg-white shadow rounded p-4">
        <h2 class="font-semibold mb-1">{{ $editando ? 'Editar cliente' : 'Nuevo cliente' }}</h2>
        <p class="text-xs text-gray-500 mb-3">
            {{ $this->esConsumidorFinal
                ? 'Consumidor final: solo el nombre es obligatorio.'
                : 'Completa los datos fiscales si emitirás CCF o notas de crédito/débito.' }}
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            {{-- Datos básicos --}}
            <div>
                <label class="text-xs text-gray-600">Tipo de cliente *</label>
                <select wire:model.live="tipo_documento" class="w-full border rounded px-2 py-1">
                    <option value="36">NIT (empresa / CCF)</option>
                    <option value="13">DUI (persona natural)</option>
                    <option value="37">Consumidor final</option>
                    <option value="03">Pasaporte</option>
                    <option value="02">Carnet de residente</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-600">
                    N° documento {{ $this->esConsumidorFinal ? '(opcional)' : '*' }}
                </label>
                <input type="text" wire:model="num_documento"
                       placeholder="{{ $this->esConsumidorFinal ? 'CF' : '' }}"
                       class="w-full border rounded px-2 py-1">
                @error('num_documento') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs text-gray-600">Nombre {{ $this->esConsumidorFinal ? '*' : 'o razón social *' }}</label>
                <input type="text" wire:model="nombre" class="w-full border rounded px-2 py-1">
                @error('nombre') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs text-gray-600">Correo (para envío del DTE)</label>
                <input type="email" wire:model="correo" class="w-full border rounded px-2 py-1">
                @error('correo') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs text-gray-600">Teléfono</label>
                <input type="text" wire:model="telefono" class="w-full border rounded px-2 py-1">
            </div>
            <div class="flex items-end pb-1">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="activo" class="border rounded">
                    Activo
                </label>
            </div>

            {{-- Datos fiscales: solo si NO es consumidor final --}}
            @unless ($this->esConsumidorFinal)
                <div class="md:col-span-3 border-t pt-3 mt-1">
                    <h3 class="text-sm font-semibold text-gray-700">Datos fiscales <span class="font-normal text-gray-400">(necesarios para CCF y notas)</span></h3>
                </div>
                <div>
                    <label class="text-xs text-gray-600">NRC</label>
                    <input type="text" wire:model="nrc" class="w-full border rounded px-2 py-1">
                </div>
                <div>
                    <label class="text-xs text-gray-600">Act. económica (CAT-019)</label>
                    <input type="text" wire:model="actividad_economica" class="w-full border rounded px-2 py-1" placeholder="10005">
                </div>
                <div>
                    <label class="text-xs text-gray-600">Desc. actividad</label>
                    <input type="text" wire:model="desc_actividad" class="w-full border rounded px-2 py-1">
                </div>
                <div>
                    <label class="text-xs text-gray-600">Nombre comercial</label>
                    <input type="text" wire:model="nombre_comercial" class="w-full border rounded px-2 py-1">
                </div>
            @endunless

            {{-- Ubicación --}}
            <div class="md:col-span-3 border-t pt-3 mt-1">
                <h3 class="text-sm font-semibold text-gray-700">Ubicación</h3>
            </div>
            <div>
                <label class="text-xs text-gray-600">Departamento</label>
                <select wire:model.live="departamento" class="w-full border rounded px-2 py-1">
                    @foreach ($departamentos as $codigo => $nombre)
                        <option value="{{ $codigo }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-600">Municipio / zona</label>
                <select wire:model="municipio" class="w-full border rounded px-2 py-1">
                    <option value="">— Seleccione —</option>
                    @foreach ($municipios as $codigo => $nombre)
                        <option value="{{ $codigo }}">{{ $nombre }}</option>
                    @endforeach
                </select>
                @error('municipio') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs text-gray-600">Distrito</label>
                <select wire:model="distrito" class="w-full border rounded px-2 py-1">
                    <option value="">— Seleccione —</option>
                    @foreach ($distritos as $codigo => $nombre)
                        <option value="{{ $codigo }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="text-xs text-gray-600">Dirección (complemento)</label>
                <input type="text" wire:model="direccion_complemento" class="w-full border rounded px-2 py-1">
            </div>
        </div>

        <div class="mt-4 flex gap-2">
            <button wire:click="guardar" class="bg-blue-600 text-white px-4 py-1 rounded">
                {{ $editando ? 'Actualizar' : 'Guardar' }}
            </button>
            @if ($editando)
                <button wire:click="cancelar" class="bg-gray-300 px-4 py-1 rounded">Cancelar</button>
            @endif
        </div>
    </div>

    {{-- Buscador --}}
    <input type="text" wire:model.live.debounce.300ms="buscar"
           placeholder="Buscar por nombre, N° documento o NRC..."
           class="w-full md:w-96 border rounded px-3 py-2">

    {{-- Listado --}}
    <div class="bg-white shadow rounded overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left p-2">Nombre</th>
                    <th class="text-left p-2">Documento</th>
                    <th class="text-left p-2">NRC</th>
                    <th class="text-left p-2">Correo</th>
                    <th class="text-left p-2">Teléfono</th>
                    <th class="text-right p-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($receptores as $r)
                    <tr class="border-t">
                        <td class="p-2">{{ $r->nombre }}</td>
                        <td class="p-2">{{ $r->tipo_documento }}-{{ $r->num_documento }}</td>
                        <td class="p-2">{{ $r->nrc }}</td>
                        <td class="p-2">{{ $r->correo }}</td>
                        <td class="p-2">{{ $r->telefono }}</td>
                        <td class="p-2 text-right whitespace-nowrap">
                            <button wire:click="editar({{ $r->id }})" class="text-blue-600 hover:underline mr-3">Editar</button>
                            <button wire:click="borrar({{ $r->id }})"
                                    wire:confirm="¿Eliminar a {{ $r->nombre }}? Los documentos emitidos conservan sus datos."
                                    class="text-red-600 hover:underline">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-4 text-center text-gray-500">Sin clientes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $receptores->links() }}
</div>