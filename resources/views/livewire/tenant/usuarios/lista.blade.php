<div>
    <h1>Usuarios del negocio</h1>

    @if (session('ok'))
        <div class="tarjeta" style="background:#dcfce7;color:#166534;">{{ session('ok') }}</div>
    @endif

    @error('general')
        <div class="tarjeta" style="background:#fee2e2;color:#991b1b;">{{ $message }}</div>
    @enderror

    <div class="tarjeta">
        <h2 style="font-size:1rem;margin-bottom:1rem;">
            {{ $editandoId ? 'Editar usuario' : 'Nuevo usuario' }}
        </h2>

        <form wire:submit="guardar" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.8rem;align-items:end;">
            <div>
                <label>Nombre</label><br>
                <input type="text" wire:model="name" style="width:100%;">
                @error('name') <div style="color:#991b1b;font-size:.8rem;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Correo</label><br>
                <input type="email" wire:model="email" style="width:100%;">
                @error('email') <div style="color:#991b1b;font-size:.8rem;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Contraseña {{ $editandoId ? '(vacío = no cambiar)' : '' }}</label><br>
                <input type="password" wire:model="password" style="width:100%;">
                @error('password') <div style="color:#991b1b;font-size:.8rem;">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Confirmar contraseña</label><br>
                <input type="password" wire:model="password_confirmation" style="width:100%;">
            </div>
            <div style="display:flex;gap:.5rem;">
                <button type="submit" class="btn btn-azul">{{ $editandoId ? 'Guardar cambios' : 'Crear usuario' }}</button>
                @if ($editandoId)
                    <button type="button" class="btn btn-gris" wire:click="cancelarEdicion">Cancelar</button>
                @endif
            </div>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($usuarios as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->activo ? 'b-validado' : 'b-rechazado' }}">
                            {{ $user->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td style="display:flex;gap:.5rem;">
                        <button class="btn btn-gris" wire:click="editar({{ $user->id }})">Editar</button>
                        <button class="btn {{ $user->activo ? 'btn-azul' : 'btn-gris' }}"
                                wire:click="toggleActivo({{ $user->id }})"
                                @if ($user->id === auth()->id()) disabled @endif>
                            {{ $user->activo ? 'Desactivar' : 'Activar' }}
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>