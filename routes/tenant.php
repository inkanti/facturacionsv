<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\DocumentoController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Rutas del tenant
|--------------------------------------------------------------------------
| Estas rutas se resuelven por dominio (demo.facturadorsv.test) y corren
| con la conexión "tenant" activa. Los dominios centrales quedan excluidos.
|
| Nota: la validación CSRF del middleware "web" ya cubre el POST de
| consultar-mh, por eso el formulario de la vista incluye @csrf.
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Vista provisional de verificación del Módulo 0
    Route::get('/', function () {
        return view('tenant.welcome');
    });

    // Módulo 4 — Consulta y descarga de documentos
    Route::get('documentos', [DocumentoController::class, 'index'])->name('documentos.index');
    Route::get('documentos/{documento}', [DocumentoController::class, 'show'])->name('documentos.show');
    Route::get('documentos/{documento}/json', [DocumentoController::class, 'descargarJson'])->name('documentos.json');
    Route::get('documentos/{documento}/pdf', [DocumentoController::class, 'descargarPdf'])->name('documentos.pdf');
    Route::post('documentos/{documento}/consultar-mh', [DocumentoController::class, 'consultarMh'])->name('documentos.consultar');
});