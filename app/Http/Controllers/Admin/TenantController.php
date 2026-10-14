<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoSuscripcion;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->string('buscar')->toString();

        $tenants = Tenant::query()
            ->with('domains')
            ->when($buscar, fn ($q) => $q->where(fn ($qq) => $qq
                ->where('id', 'like', "%{$buscar}%")
                ->orWhere('name', 'like', "%{$buscar}%")))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Una sola consulta para las suscripciones de toda la página
        $suscripciones = Subscription::whereIn('tenant_id', $tenants->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('tenant_id');

        return view('admin.tenants.index', compact('tenants', 'suscripciones', 'buscar'));
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('domains');
        $suscripciones = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->orderByDesc('id')
            ->get();
        $suscripcionActual = $suscripciones->first();
        $planes = Plan::visibles()->get();

        return view('admin.tenants.show', compact('tenant', 'suscripciones', 'suscripcionActual', 'planes'));
    }

    public function asignarPlan(Request $request, Tenant $tenant)
    {
        $datos = $request->validate([
            'plan_id' => ['required', 'exists:planes,id'],
            'meses' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        // Cerrar la suscripción anterior (si hay)
        Subscription::where('tenant_id', $tenant->getTenantKey())
            ->whereIn('estado', [EstadoSuscripcion::Trial, EstadoSuscripcion::Activa])
            ->update([
                'estado' => EstadoSuscripcion::Cancelada,
                'cancelado_en' => now(),
            ]);

        Subscription::create([
            'tenant_id' => $tenant->getTenantKey(),
            'plan_id' => $datos['plan_id'],
            'estado' => EstadoSuscripcion::Activa,
            'inicia_en' => now()->toDateString(),
            'vence_en' => now()->addMonths((int) $datos['meses'])->toDateString(),
        ]);

        return back()->with('exito', 'Plan asignado correctamente.');
    }

    public function extender(Request $request, Tenant $tenant)
    {
        $datos = $request->validate(['meses' => ['required', 'integer', 'min:1', 'max:12']]);

        $suscripcion = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->orderByDesc('id')
            ->first();

        if (! $suscripcion) {
            return back()->withErrors(['meses' => 'Este tenant no tiene suscripción.']);
        }

        // Si aún no vence, se suma desde la fecha actual de vencimiento
        $base = $suscripcion->vence_en && $suscripcion->vence_en->greaterThan(now()->startOfDay())
            ? $suscripcion->vence_en->copy()
            : now()->startOfDay();

        $suscripcion->update([
            'estado' => EstadoSuscripcion::Activa,
            'vence_en' => $base->addMonths((int) $datos['meses'])->toDateString(),
        ]);

        return back()->with('exito', "Suscripción extendida {$datos['meses']} mes(es).");
    }

    public function suspender(Tenant $tenant)
    {
        Subscription::where('tenant_id', $tenant->getTenantKey())
            ->whereIn('estado', [EstadoSuscripcion::Trial, EstadoSuscripcion::Activa])
            ->update(['estado' => EstadoSuscripcion::Suspendida]);

        return back()->with('exito', 'Tenant suspendido.');
    }

    public function reactivar(Tenant $tenant)
    {
        $suscripcion = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->orderByDesc('id')
            ->first();

        if (! $suscripcion) {
            return $this->iniciarTrial($tenant); // red de seguridad
        }

        if ($suscripcion->estado === EstadoSuscripcion::Trial) {
            return back()->with('exito', 'El tenant sigue en su período de prueba.');
        }

        $vence = $suscripcion->vence_en && $suscripcion->vence_en->greaterThanOrEqualTo(now()->startOfDay())
            ? $suscripcion->vence_en
            : now()->addMonth()->toDateString();

        $suscripcion->update([
            'estado' => EstadoSuscripcion::Activa,
            'vence_en' => $vence,
        ]);

        return back()->with('exito', 'Tenant reactivado.');
    }

    public function iniciarTrial(Tenant $tenant)
    {
        Subscription::create([
            'tenant_id' => $tenant->getTenantKey(),
            'estado' => EstadoSuscripcion::Trial,
            'trial_hasta' => now()->addDays(14)->toDateString(),
        ]);

        return back()->with('exito', 'Prueba gratis de 14 días iniciada.');
    }
}