<?php

namespace App\Livewire\Tenant\Documentos;

use App\Models\Producto;
use App\Models\Receptor;
use App\Services\Dte\EmisorDocumentos;
use Livewire\Component;
use Throwable;

/**
 * Tenant — pantalla de emisión de DTE.
 * Soporta CCF (03, crédito fiscal) y Factura (01, consumidor final).
 * Totales calculados en vivo en cada cambio de ítems.
 */
class Crear extends Component
{
    /** Tipo de DTE: '03' = CCF, '01' = Factura (consumidor final). */
    public string $tipo_documento = '03';

    public $receptor_id = '';

    /** @var array<int, array{producto_id: mixed, cantidad: int}> */
    public array $items = [];

    public function mount(): void
    {
        $this->items = [['producto_id' => '', 'cantidad' => 1]];
    }

    /** Al cambiar el tipo de documento: si el receptor elegido no califica, se limpia. */
    public function updatedTipoDocumento(): void
    {
        if ($this->tipo_documento !== '03' || empty($this->receptor_id)) {
            return;
        }

        $receptor = Receptor::find($this->receptor_id);

        if ($receptor && (empty($receptor->nrc) || empty($receptor->actividad_economica))) {
            $this->receptor_id = '';
        }
    }

    public function agregarItem(): void
    {
        $this->items[] = ['producto_id' => '', 'cantidad' => 1];
    }

    public function quitarItem(int $indice): void
    {
        unset($this->items[$indice]);
        $this->items = array_values($this->items);

        if (count($this->items) === 0) {
            $this->agregarItem();
        }
    }

    public function emitir()
    {
        $this->validate([
            'tipo_documento' => ['required', 'in:03,01'],
            'receptor_id' => ['required', 'exists:receptores,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01'],
        ], [], [
            'tipo_documento' => 'tipo de documento',
            'receptor_id' => 'receptor',
            'items.*.producto_id' => 'producto',
            'items.*.cantidad' => 'cantidad',
        ]);

        $receptor = Receptor::findOrFail($this->receptor_id);

        // El CCF (03) exige que el receptor tenga NRC y actividad económica
        // (es crédito fiscal). La Factura (01) no lo exige.
        if ($this->tipo_documento === '03'
            && (empty($receptor->nrc) || empty($receptor->actividad_economica))) {
            $this->addError('general', 'El CCF exige que el receptor tenga NRC y actividad económica. Elija una Factura (01) para consumidores finales.');

            return;
        }

        try {
            $documento = app(EmisorDocumentos::class)->crearBorrador($receptor, $this->items);
            $documento->update(['tipo_documento' => $this->tipo_documento]);
            app(EmisorDocumentos::class)->emitir($documento);
        } catch (Throwable $e) {
            $this->addError('general', 'No se pudo emitir: ' . $e->getMessage());

            return;
        }

        return redirect()
            ->route('documentos.show', $documento)
            ->with('exito', 'Documento en cola de firma y transmisión. Recarga el detalle en unos segundos.');
    }

    /** Totales en vivo; render corre en cada cambio de propiedades. */
    private function calcularTotales(): array
    {
        $subtotal = 0.0;
        $iva = 0.0;

        foreach ($this->items as $item) {
            $producto = Producto::find($item['producto_id'] ?? null);
            $cantidad = (float) ($item['cantidad'] ?? 0);

            if (! $producto || $cantidad <= 0) {
                continue;
            }

            $linea = round((float) $producto->precio_unitario * $cantidad, 2);
            $subtotal += $linea;
            $iva += round($linea * $producto->tipo_iva->porcentaje() / 100, 2);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'iva' => round($iva, 2),
            'total' => round($subtotal + $iva, 2),
        ];
    }

    /** Receptores filtrados según el tipo de DTE elegido. */
    private function receptoresParaTipo(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Receptor::orderBy('nombre');

        if ($this->tipo_documento === '03') {
            // El CCF exige NRC + actividad económica: solo receptores aptos.
            $query->whereNotNull('nrc')->where('nrc', '!=', '')
                ->whereNotNull('actividad_economica')->where('actividad_economica', '!=', '');
        }

        return $query->get();
    }

    public function render()
    {
        return view('livewire.tenant.documentos.crear', [
            'receptores' => $this->receptoresParaTipo(),
            'productos' => Producto::orderBy('nombre')->get(),
            'totales' => $this->calcularTotales(),
        ])->layout('tenant.layout');
    }
}