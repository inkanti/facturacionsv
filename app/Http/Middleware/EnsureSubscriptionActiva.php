<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Módulo 5 — Bloquea el acceso al tenant si la suscripción no está vigente.
 * La ruta "suscripcion.estado" NO lleva este middleware (siempre accesible).
 */
class EnsureSubscriptionActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            return $next($request);
        }

        $suscripcion = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->orderByDesc('id')
            ->first();

        if ($suscripcion && $suscripcion->estaVigente()) {
            return $next($request);
        }

        return redirect()->route('suscripcion.estado');
    }
}