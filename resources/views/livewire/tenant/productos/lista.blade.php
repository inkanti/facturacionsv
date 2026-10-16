{{-- Módulo 11 — CRUD de productos/servicios (columnas verificadas contra tenant_demo.productos) --}}
<div class="max-w-6xl mx-auto p-6 space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Productos y servicios</h1>
        <a href="{{ url('/documentos') }}" class="text-sm text-blue-600 hover:underline">&larr; Volver a documentos</a>
    </div>

    @if (session('ok'))
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-2 rounded">{{ session('ok') }}</div>
    @endif

    {{-- Formulario crear/editar --}}
    <div class="bg-white shadow rounded p-4">
        <h2 class="font-semibold mb-3">{{ $editando ? 'Editar producto' : 'Nuevo producto' }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-gray-600">Código *</label>
                <input type="text" wire:model="codigo" class="w-full border rounded px-2 py-1">
                @error('codigo') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-gray-600">Nombre *</label>
                <input type="text" wire:model="nombre" class="w-full border rounded px-2 py-1">
                @error('nombre') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs text-gray-600">Tipo ítem (CAT-011) *</label>
                <select wire:model="tipo" class="w-full border rounded px-2 py-1">
                    <option value="1">1 - Bienes</option>
                    <option value="2">2 - Servicios</option>
                </select>
                @error('tipo') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-gray-600">Descripción</label>
                <input type="text" wire:model="descripcion" class="w-full border rounded px-2 py-1">
            </div>
            <div>
                <label class="text-xs text-gray-600">Precio unitario (sin IVA) *</label>
                <input type="number" step="0.01" min="0" wire:model="precio_unitario" class="w-full border rounded px-2 py-1">
                @error('precio_unitario') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs text-gray-600">Unidad (CAT-014)</label>
                <input type="number" min="1" max="99" wire:model="unidad_medida" class="w-full border rounded px-2 py-1">
                @error('unidad_medida') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs text-gray-600">Tratamiento de IVA *</label>
                <select wire:model="tipo_iva" class="w-full border rounded px-2 py-1">
                    <option value="">— Seleccione —</option>
                    @foreach ($tiposIva as $opcion)
                        <option value="{{ $opcion->value }}">{{ $opcion->name }}</option>
                    @endforeach
                </select>
                @error('tipo_iva') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="text-xs text-gray-600">% IVA</label>
                <input type="number" step="0.01" min="0" max="99.99" wire:model="porcentaje_iva" class="w-full border rounded px-2 py-1">
                @error('porcentaje_iva') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="flex items-end pb-1">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="activo" class="border rounded">
                    Activo
                </label>
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
           placeholder="Buscar por código o nombre..."
           class="w-full md:w-96 border rounded px-3 py-2">

    {{-- Listado --}}
    <div class="bg-white shadow rounded overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left p-2">Código</th>
                    <th class="text-left p-2">Nombre</th>
                    <th class="text-left p-2">Tipo</th>
                    <th class="text-right p-2">P. unitario</th>
                    <th class="text-left p-2">IVA</th>
                    <th class="text-left p-2">Estado</th>
                    <th class="text-right p-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $p)
                    <tr class="border-t">
                        <td class="p-2">{{ $p->codigo }}</td>
                        <td class="p-2">{{ $p->nombre }}</td>
                        <td class="p-2">{{ $p->tipo == '2' ? 'Servicio' : 'Bien' }}</td>
                        <td class="p-2 text-right">${{ number_format((float) $p->precio_unitario, 2) }}</td>
                        <td class="p-2">{{ $p->tipo_iva->name }} {{ (float) $p->porcentaje_iva }}%</td>
                        <td class="p-2">{{ $p->activo ? 'Activo' : 'Inactivo' }}</td>
                        <td class="p-2 text-right whitespace-nowrap">
                            <button wire:click="editar({{ $p->id }})" class="text-blue-600 hover:underline mr-3">Editar</button>
                            <button wire:click="borrar({{ $p->id }})"
                                    wire:confirm="¿Eliminar {{ $p->nombre }}? Los documentos emitidos conservan sus datos."
                                    class="text-red-600 hover:underline">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-4 text-center text-gray-500">Sin productos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $productos->links() }}
</div>