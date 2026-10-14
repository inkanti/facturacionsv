<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoSuscripcion;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function index()
    {
        $totales = [
            'tenants' => Tenant::count(),
            'activas' => Subscription::where('estado', EstadoSuscripcion::Activa)->count(),
            'trials' => Subscription::where('estado', EstadoSuscripcion::Trial)->count(),
            'bloqueadas' => Subscription::whereIn('estado', [
                EstadoSuscripcion::Vencida,
                EstadoSuscripcion::Suspendida,
                EstadoSuscripcion::Cancelada,
            ])->count(),
        ];

        $ingresoMensual = Subscription::where('estado', EstadoSuscripcion::Activa)
            ->whereNotNull('plan_id')
            ->with('plan')
            ->get()
            ->sum(fn ($s) => (float) $s->plan->precio_mensual);

        $ultimosTenants = Tenant::latest('created_at')->take(5)->get();

        return view('admin.dashboard', compact('totales', 'ingresoMensual', 'ultimosTenants'));
    }
}