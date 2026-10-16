<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetupController extends Controller
{
    // Setup inicial: solo existe si el tenant NO tiene usuarios
    public function mostrar()
    {
        abort_unless(User::count() === 0, 404);

        return view('tenant.auth.setup');
    }

    public function crear(Request $request)
    {
        abort_unless(User::count() === 0, 403);

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        // El cast 'hashed' del modelo encripta la contraseña automáticamente
        $user = User::create($datos);

        Auth::login($user);

        return redirect()->route('documentos.index');
    }
}