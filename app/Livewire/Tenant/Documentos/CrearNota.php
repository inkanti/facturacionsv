<?php

namespace App\Livewire\Tenant\Documentos;

use App\Enums\EstadoDocumento;
use App\Models\Documento;
use App\Services\Dte\EmisorDocumentos;
use Livewire\Component;
use Throwable;

/**
 * Emisión de nota de crédito (05) o débito (06) sobre un CCF validado.
 * Las líneas se precargan del documento afectado y solo se ajustan cantidades.
 */
class CrearNota extends Component
{
    public Documento $padre;

    /** '05' = NC, '06' = ND. */
    public string $tipo = '05';

    /** @var array<int, array{detalle_id: int, cantidad: float}> */
    public array $items = [];

    public function mount(Documento $documento, string $tipo): void
    {
        abort_unless($documento->estado === EstadoDocumento::Validado, 404);
        abort_unless(in_array($tipo, ['05', '06'], true), 404);
        abort_if($documento->tipo_documento !== '03', 404); // por ahora solo sobre CCF

        $this->padre = $documento;
        $this->tipo = $tipo;

        // Pre-cargar las líneas del documento afectado con sus cantidades
        $this->items = $documento->detalles()
            ->orderBy('id')
            ->get()
            ->map(fn ($d) => ['detalle_id' => $d->id, 'cantidad' => (float) $d->cantidad])
            ->values()
            ->all();
    }

    public function quitarItem(int $indice): void
    {
        unset($this->items[$indice]);
        $this->items = array_values($this->items);

        if (count($this->items) === 0) {
            $this->addError('general', 'La nota debe conservar al menos una línea.');
        }
    }

    public function emitirNota()
    {
        $this->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.detalle_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01'],
        ], [], [
            'items.*.cantidad' => 'cantidad',
        ]);

        // Las cantidades no pueden superar las del documento afectado
        $detallesPadre = $this->padre->detalles()->get()->keyBy('id');
        $itemsEmision = [];

        foreach ($this->items as $item) {
            $detalle = $detallesPadre->get($item['detalle_id']);

            if (! $detalle) {
                $this->addError('general', 'Una de las líneas no pertenece al documento afectado.');

                return;
            }

            if ((float) $item['cantidad'] > (float) $detalle->cantidad) {
                $this->addError('general', "La cantidad de '{$detalle->nombre}' no puede superar la del documento afectado ({$detalle->cantidad}).");

                return;
            }

            $itemsEmision[] = [
                'producto_id' => $detalle->producto_id,
                'cantidad' => (float) $item['cantidad'],
            ];
        }

        try {
            $nota = app(EmisorDocumentos::class)->crearBorradorNota($this->padre, $this->tipo, $itemsEmision);
            app(EmisorDocumentos::class)->emitir($nota);
        } catch (Throwable $e) {
            $this->addError('general', 'No se pudo emitir la nota: ' . $e->getMessage());

            return;
        }

        return redirect()
            ->route('documentos.show', $nota)
            ->with('exito', 'Nota en cola de firma y transmisión. Recarga el detalle en unos segundos.');
    }

    /** Totales en vivo a partir de las líneas del documento afectado. */
    private function calcularTotales(): array
    {
        $detallesPadre = $this->padre->detalles()->get()->keyBy('id');
        $subtotal = 0.0;
        $iva = 0.0;

        foreach ($this->items as $item) {
            $detalle = $detallesPadre->get($item['detalle_id'] ?? null);
            $cantidad = (float) ($item['cantidad'] ?? 0);

            if (! $detalle || $cantidad <= 0) {
                continue;
            }

            $linea = round((float) $detalle->precio_unitario * $cantidad, 2);
            $subtotal += $linea;
            $iva += round($linea * $detalle->tipo_iva->porcentaje() / 100, 2);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'iva' => round($iva, 2),
            'total' => round($subtotal + $iva, 2),
        ];
    }

    public function render()
    {
        return view('livewire.tenant.documentos.crear-nota', [
            'detallesPadre' => $this->padre->detalles()->orderBy('id')->get()->keyBy('id'),
            'totales' => $this->calcularTotales(),
        ])->layout('tenant.layout');
    }
}