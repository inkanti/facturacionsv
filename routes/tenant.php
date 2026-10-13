<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware;

/*
|--------------------------------------------------------------------------
| Rutas de tenant
|--------------------------------------------------------------------------
| El middleware resuelve el tenant por dominio y bloquea el acceso
| desde dominios centrales.
|
| Vista provisional: se reemplaza en el módulo de UI.
*/
Route::middleware([
    'web',
    Middleware\InitializeTenancyByDomain::class,
    Middleware\PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', function () {
        return view('tenant.welcome', [
            'tenant'   => tenancy()->tenant, // tenant actual (resuelto por dominio)
            'database' => config('database.connections.tenant.database'),
        ]);
    });
});