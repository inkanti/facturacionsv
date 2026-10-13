<?php

namespace App\Providers;

use App\Contracts\ProveedorDte;
use App\Services\Dte\ProveedorDteMH;
use App\Services\Dte\ProveedorDteSimulado;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Binding del proveedor de DTE (simulado en dev, MH en producción)
        $this->app->bind(ProveedorDte::class, function ($app) {
            return match (config('dte.proveedor')) {
                'mh' => $app->make(ProveedorDteMH::class),
                default => new ProveedorDteSimulado(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}