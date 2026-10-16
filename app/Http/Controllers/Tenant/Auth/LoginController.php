<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    // Muestra el login; si el tenant aún no tiene usuarios, manda al setup inicial
    public function mostrar()
    {
        if (User::count() === 0) {
            return redirect()->route('tenant.setup');
        }

        return view('tenant.auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credenciales, $request->boolean('remember'))) {
            // Módulo 12: usuarios desactivados no entran
            if (! auth()->user()->activo) {
                Auth::logout();

                return back()->withErrors(['email' => 'Este usuario está desactivado.'])
                    ->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('documentos.index'));
        }

        return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}