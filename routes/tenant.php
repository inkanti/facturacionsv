<?php

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

    // Módulo 5: estado de suscripción — SIEMPRE accesible (no lleva bloqueo)
    Route::get('suscripcion', [SuscripcionController::class, 'estado'])->name('suscripcion.estado');

    // Rutas de negocio: solo con suscripción vigente
    Route::middleware('suscripcion.activa')->group(function () {

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