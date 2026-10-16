<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Módulo 12: invitados van al login que corresponda según el contexto
        $middleware->redirectGuestsTo(function () {
            if (tenancy()->initialized) {
                return route('tenant.login'); // dominio del negocio
            }

            return route('admin.login'); // dominio central
        });

        // Usuarios autenticados que visitan login/setup vuelven a su panel
        $middleware->redirectUsersTo(function () {
            if (tenancy()->initialized) {
                return route('documentos.index');
            }

            return '/admin';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();