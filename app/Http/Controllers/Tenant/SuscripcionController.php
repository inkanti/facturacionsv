<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Subscription;

/**
 * Módulo 5 — Pantalla de estado de suscripción (accesible aunque esté vencida).
 */
class SuscripcionController extends Controller
{
    public function estado()
    {
        $suscripcion = Subscription::where('tenant_id', tenant()->getTenantKey())
            ->orderByDesc('id')
            ->first();

        return view('tenant.suscripcion.estado', compact('suscripcion'));
    }
}