<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Módulo 5 — Login de administradores (guard "admin").
 */
class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // El estado 'activo' entra en el WHERE del attempt
        if (Auth::guard('admin')->attempt($credenciales + ['estado' => 'activo'], $request->boolean('remember'))) {
            $request->session()->regenerate();
            AdminUser::whereKey(Auth::guard('admin')->id())->update(['ultimo_acceso_en' => now()]);

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Credenciales inválidas o usuario inactivo.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}