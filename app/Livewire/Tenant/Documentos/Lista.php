<?php

namespace App\Livewire\Tenant\Documentos;

use App\Models\Documento;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tenant — listado de documentos con filtros en vivo.
 */
class Lista extends Component
{
    use WithPagination;

    public string $buscar = '';

    public string $estado = '';

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedEstado(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $documentos = Documento::query()
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->when($this->buscar, fn ($q) => $q->where(fn ($qq) => $qq
                ->where('numero_control', 'like', "%{$this->buscar}%")
                ->orWhere('receptor_nombre', 'like', "%{$this->buscar}%")))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.tenant.documentos.lista', compact('documentos'))
            ->layout('tenant.layout');
    }
}