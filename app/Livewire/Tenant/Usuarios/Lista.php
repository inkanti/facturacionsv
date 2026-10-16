<?php

namespace App\Livewire\Tenant\Usuarios;

use App\Models\User;
use Livewire\Component;

class Lista extends Component
{
    // Formulario (crear / editar)
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public ?int $editandoId = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Al editar se ignora el propio registro en la regla unique
            'email' => ['required', 'email', 'max:255',
                'unique:users,email' . ($this->editandoId ? ',' . $this->editandoId : '')],
            // Al editar la contraseña es opcional (solo se cambia si se escribe)
            'password' => [$this->editandoId ? 'nullable' : 'required', 'confirmed', 'min:8'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.unique' => 'Ese correo ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    public function guardar()
    {
        $datos = $this->validate();

        if ($this->editandoId) {
            $user = User::findOrFail($this->editandoId);
            $user->update([
                'name' => $datos['name'],
                'email' => $datos['email'],
            ]);
            // El cast 'hashed' del modelo encripta sola
            if (! empty($datos['password'])) {
                $user->update(['password' => $datos['password']]);
            }
            session()->flash('ok', 'Usuario actualizado.');
        } else {
            User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => $datos['password'],
            ]);
            session()->flash('ok', 'Usuario creado.');
        }

        $this->cancelarEdicion();
    }

    public function editar($id)
    {
        $user = User::findOrFail($id);

        $this->editandoId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function cancelarEdicion()
    {
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'editandoId']);
        $this->resetValidation();
    }

    // Desactivar / reactivar (no se puede desactivar a uno mismo)
    public function toggleActivo($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->addError('general', 'No podés desactivar tu propio usuario.');

            return;
        }

        $user->update(['activo' => ! $user->activo]);
    }

    public function render()
    {
        return view('livewire.tenant.usuarios.lista', [
            'usuarios' => User::orderBy('name')->get(),
        ])->layout('tenant.layout');
    }
}