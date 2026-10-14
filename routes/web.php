<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\TenantController;

/*
|--------------------------------------------------------------------------
| Rutas centrales
|--------------------------------------------------------------------------
| Solo landing y, en el futuro, registro de negocios y panel admin.
| Los dominios centrales se definen en config/tenancy.php.
*/
Route::get('/', function () {
    return 'FACTURADOR SV — portal central (módulo 5: registro y suscripciones)';
});

/*
|--------------------------------------------------------------------------
| Panel de administración (dominio central)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->name('login.post');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
        Route::post('tenants/{tenant}/asignar-plan', [TenantController::class, 'asignarPlan'])->name('tenants.asignar-plan');
        Route::post('tenants/{tenant}/extender', [TenantController::class, 'extender'])->name('tenants.extender');
        Route::post('tenants/{tenant}/suspender', [TenantController::class, 'suspender'])->name('tenants.suspender');
        Route::post('tenants/{tenant}/reactivar', [TenantController::class, 'reactivar'])->name('tenants.reactivar');
        Route::post('tenants/{tenant}/iniciar-trial', [TenantController::class, 'iniciarTrial'])->name('tenants.iniciar-trial');

        Route::get('planes', [PlanController::class, 'index'])->name('planes.index');
        Route::get('planes/{plan}/editar', [PlanController::class, 'edit'])->name('planes.edit');
        Route::put('planes/{plan}', [PlanController::class, 'update'])->name('planes.update');
    });
});