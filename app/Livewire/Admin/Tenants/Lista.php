<?php

namespace App\Livewire\Admin\Tenants;

use App\Models\Subscription;
use App\Models\Tenant;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Panel admin — listado de tenants con búsqueda y paginación en vivo.
 */
class Lista extends Component
{
    use WithPagination;

    public string $buscar = '';

    /** Al cambiar la búsqueda volvemos a la página 1 */
    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $tenants = Tenant::query()
            ->with('domains')
            ->when($this->buscar, fn ($q) => $q->where(fn ($qq) => $qq
                ->where('id', 'like', "%{$this->buscar}%")
                ->orWhere('name', 'like', "%{$this->buscar}%")))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Una sola consulta para las suscripciones de toda la página
        $suscripciones = Subscription::whereIn('tenant_id', $tenants->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('tenant_id');

        return view('livewire.admin.tenants.lista', compact('tenants', 'suscripciones'))
            ->layout('admin.layout');
    }
}