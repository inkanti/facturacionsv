<?php

namespace App\Livewire\Tenant\Documentos;

use App\Models\Documento;
use Livewire\Component;

/**
 * Tenant — detalle de un documento (descargas y consulta MH se quedan en
 * rutas normales: devuelven archivos / hacen POST->redirect).
 */
class Mostrar extends Component
{
    public Documento $documento;

    public function mount(Documento $documento): void
    {
        $this->documento = $documento;
    }

    public function render()
    {
        return view('livewire.tenant.documentos.mostrar', [
            'documento' => $this->documento->fresh(),
        ])->layout('tenant.layout');
    }
}