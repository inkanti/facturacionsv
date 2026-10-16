<?php

use App\Http\Controllers\Tenant\Auth\LoginController;
use App\Http\Controllers\Tenant\Auth\SetupController;
use App\Http\Controllers\Tenant\DocumentoController;
use App\Http\Controllers\Tenant\SuscripcionController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Rutas de tenant
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::get('/', function () {
        $tenant = tenant();

        return view('tenant.welcome', [
            'tenant' => $tenant,
            'database' => config('database.connections.tenant.database'),
        ]);
    })->name('tenant.welcome');

    // Módulo 12 — acceso (solo invitados)
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'mostrar'])->name('tenant.login');
        Route::post('login', [LoginController::class, 'login']);
        Route::get('setup', [SetupController::class, 'mostrar'])->name('tenant.setup');
        Route::post('setup', [SetupController::class, 'crear']);
    });

    Route::post('logout', [LoginController::class, 'logout'])->name('tenant.logout');

    // Módulo 12: suscripción requiere sesión, pero NO suscripción.activa (debe verse aunque venza)
    Route::middleware('auth')->group(function () {
        Route::get('suscripcion', [SuscripcionController::class, 'estado'])->name('suscripcion.estado');
        Route::get('usuarios', \App\Livewire\Tenant\Usuarios\Lista::class)->name('usuarios.index');
    });

    // Rutas de negocio: con sesión + suscripción vigente
    Route::middleware(['auth', 'suscripcion.activa'])->group(function () {

        Route::get('documentos', \App\Livewire\Tenant\Documentos\Lista::class)->name('documentos.index');
        Route::get('documentos/crear', \App\Livewire\Tenant\Documentos\Crear::class)->name('documentos.crear');
        Route::get('documentos/{documento}', \App\Livewire\Tenant\Documentos\Mostrar::class)->name('documentos.show');
        Route::get('documentos/{documento}/notas/crear/{tipo}', \App\Livewire\Tenant\Documentos\CrearNota::class)
            ->name('documentos.notas.crear');
        // Módulo 11 — catálogos
        Route::get('receptores', \App\Livewire\Tenant\Receptores\Lista::class)->name('receptores.index');
        Route::get('productos', \App\Livewire\Tenant\Productos\Lista::class)->name('productos.index');
        Route::get('documentos/{documento}/json', [DocumentoController::class, 'descargarJson'])->name('documentos.json');
        Route::get('documentos/{documento}/pdf', [DocumentoController::class, 'descargarPdf'])->name('documentos.pdf');
        Route::post('documentos/{documento}/consultar-mh', [DocumentoController::class, 'consultarMh'])->name('documentos.consultar-mh');
    });
});