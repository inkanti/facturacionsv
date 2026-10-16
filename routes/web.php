<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas centrales (facturadorsv.test)
|--------------------------------------------------------------------------
*/

// La raíz lleva al panel admin
Route::get('/', function () {
    return redirect()->route('admin.login');
});

/*
|--------------------------------------------------------------------------
| Panel de administración
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Solo invitados (no autenticados)
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->name('login.post');
    });

    // Solo administradores autenticados
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Tenants: índice en Livewire, gestión con controlador (por ahora)
        Route::get('tenants', \App\Livewire\Admin\Tenants\Lista::class)->name('tenants.index');
        Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
        Route::post('tenants/{tenant}/asignar-plan', [TenantController::class, 'asignarPlan'])->name('tenants.asignar-plan');
        Route::post('tenants/{tenant}/extender', [TenantController::class, 'extender'])->name('tenants.extender');
        Route::post('tenants/{tenant}/suspender', [TenantController::class, 'suspender'])->name('tenants.suspender');
        Route::post('tenants/{tenant}/reactivar', [TenantController::class, 'reactivar'])->name('tenants.reactivar');
        Route::post('tenants/{tenant}/iniciar-trial', [TenantController::class, 'iniciarTrial'])->name('tenants.iniciar-trial');

        // Planes
        Route::get('planes', [PlanController::class, 'index'])->name('planes.index');
        Route::get('planes/{plan}/editar', [PlanController::class, 'edit'])->name('planes.edit');
        Route::put('planes/{plan}', [PlanController::class, 'update'])->name('planes.update');
    });
});