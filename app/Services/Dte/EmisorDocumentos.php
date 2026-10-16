<?php

namespace App\Services\Dte;

use App\Enums\EstadoDocumento;
use App\Enums\TipoDocumento;
use App\Enums\TipoIva;
use App\Jobs\FirmarTransmitirDocumento;
use App\Models\Documento;
use App\Models\Emisor;
use App\Models\Producto;
use App\Models\Receptor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class EmisorDocumentos
{
    /**
     * Crea un borrador con su detalle y totales calculados.
     *
     * @param array $items [['producto_id' => int, 'cantidad' => float], ...]
     */
    public function crearBorrador(Receptor $receptor, array $items): Documento
    {
        if (empty($items)) {
            throw new InvalidArgumentException('El documento debe tener al menos un ítem.');
        }

        return DB::transaction(function () use ($receptor, $items) {
            $documento = Documento::create([
                ...$this->snapshotReceptor($receptor),
                'receptor_id' => $receptor->id,
                'estado' => EstadoDocumento::Borrador,
            ]);

            $this->attachDetalles($documento, $items);

            return $this->recalcularTotales($documento);
        });
    }

    /**
     * Crea un borrador de nota de crédito (05) o débito (06) sobre un
     * documento validado. El receptor y los ítems base salen del padre.
     *
     * @param array $items [['producto_id' => int, 'cantidad' => float], ...]
     */
    public function crearBorradorNota(Documento $padre, string $tipoDocumento, array $items): Documento
    {
        if (! in_array($tipoDocumento, [TipoDocumento::Nc->value, TipoDocumento::Nd->value], true)) {
            throw new InvalidArgumentException('Tipo de nota inválido: solo 05 (NC) o 06 (ND).');
        }

        if ($padre->estado !== EstadoDocumento::Validado) {
            throw new LogicException('Solo se pueden emitir notas sobre documentos en estado Validado.');
        }

        // Por ahora las notas se emiten solo sobre CCF (03): el receptor de la
        // FE de consumidor final suele no tener los datos completos que el
        // schema v4 de notas exige (nrc, actividad, dirección, etc.).
        if ($padre->tipo_documento !== TipoDocumento::Ccf->value) {
            throw new LogicException('Por ahora las notas solo se emiten sobre CCF (03).');
        }

        if (empty($items)) {
            throw new InvalidArgumentException('La nota debe tener al menos un ítem.');
        }

        $receptor = $padre->receptor;

        return DB::transaction(function () use ($padre, $tipoDocumento, $items, $receptor) {
            $documento = Documento::create([
                ...$this->snapshotReceptor($receptor),
                'tipo_documento' => $tipoDocumento,
                'receptor_id' => $receptor->id,
                'documento_padre_id' => $padre->id,
                'estado' => EstadoDocumento::Borrador,
            ]);

            $this->attachDetalles($documento, $items);

            return $this->recalcularTotales($documento);
        });
    }

    /** Crea el detalle del documento a partir de los ítems elegidos. */
    private function attachDetalles(Documento $documento, array $items): void
    {
        foreach ($items as $item) {
            $producto = Producto::activos()->findOrFail($item['producto_id']);
            $cantidad = (float) ($item['cantidad'] ?? 0);

            if ($cantidad <= 0) {
                throw new InvalidArgumentException("Cantidad inválida para el producto {$producto->codigo}.");
            }

            $subtotal = round($cantidad * (float) $producto->precio_unitario, 2);
            $iva = $producto->tipo_iva === TipoIva::Gravado
                ? round($subtotal * (float) $producto->porcentaje_iva / 100, 2)
                : 0.0;

            $documento->detalles()->create([
                'producto_id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'tipo_item' => (int) $producto->tipo,            // CAT-011
                'unidad_medida' => (int) $producto->unidad_medida, // CAT-014
                'cantidad' => $cantidad,
                'precio_unitario' => $producto->precio_unitario,
                'tipo_iva' => $producto->tipo_iva,
                'iva' => $iva,
                'subtotal' => $subtotal,
            ]);
        }
    }

    /**
     * Emite el documento: asigna número de control + código de generación
     * (convención 7: solo al emitir) y encola firma + transmisión.
     */
    public function emitir(Documento $documento): void
    {
        if ($documento->estado !== EstadoDocumento::Borrador || $documento->numero_control !== null) {
            throw new LogicException('Solo se puede emitir un borrador que no está en proceso.');
        }

        // Validación mínima para CCF según schema v4
        if ($documento->tipo_documento === TipoDocumento::Ccf->value) {
            $receptor = $documento->receptor;
            if (empty($receptor->nrc) || empty($receptor->actividad_economica)) {
                throw new LogicException('El receptor debe tener NRC y actividad económica para emitir un CCF.');
            }
        }

        $documento->update([
            ...$this->snapshotReceptor($documento->receptor), // refrescar por si el receptor cambió
            'numero_control' => $this->generarNumeroControl($documento->tipo_documento),
            'codigo_generacion' => strtoupper((string) Str::uuid()), // schema exige mayúsculas
            'fecha_emision' => now(),
        ]);

        FirmarTransmitirDocumento::dispatch(
            documentoId: $documento->id,
            tenantId: tenant('id'),
        )->onQueue('dte');
    }

    /** Recalcula totales desde el detalle (única fuente de verdad). */
    public function recalcularTotales(Documento $documento): Documento
    {
        $detalles = $documento->detalles()->get();

        $subtotal = round((float) $detalles->sum('subtotal'), 2);
        $iva = round((float) $detalles->sum('iva'), 2);

        $documento->update([
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => round($subtotal + $iva, 2),
        ]);

        return $documento->refresh();
    }

    /** Snapshot completo del receptor: lo consume el GeneradorJsonDte. */
    private function snapshotReceptor(Receptor $receptor): array
    {
        return [
            'receptor_tipo_documento' => $receptor->tipo_documento,
            'receptor_num_documento' => $receptor->num_documento,
            'receptor_nombre' => $receptor->nombre,
            'receptor_correo' => $receptor->correo,
            'receptor_snapshot' => $receptor->only([
                'tipo_documento', 'num_documento', 'nrc', 'nombre', 'nombre_comercial',
                'actividad_economica', 'desc_actividad', 'telefono', 'correo',
                'departamento', 'municipio', 'distrito', 'direccion_complemento',
            ]),
        ];
    }

    /**
     * Número de control oficial (regex del schema v4):
     * DTE-{tipo}-{letra}{codEstable 3}P{codPuntoVenta 3}-{secuencia 15}
     * Letra por CAT-009: 01 Sucursal=S, 02 Casa Matriz=M, 04 Bodega=B, 07 Patio=P
     *
     * El correlativo se calcula como MÁXIMO existente + 1 (no como conteo):
     * count()+1 falla cuando hay huecos en la secuencia (documentos eliminados).
     */
    private function generarNumeroControl(string $tipoDocumento): string
    {
        $emisor = Emisor::activo()->firstOrFail();

        $letra = match ($emisor->tipo_establecimiento) {
            '01' => 'S', '02' => 'M', '04' => 'B', '07' => 'P',
            default => 'M',
        };

        // La secuencia son los últimos 15 caracteres del número de control;
        // al ir con ceros a la izquierda, el max() alfabético = max numérico.
        $ultimo = Documento::where('tipo_documento', $tipoDocumento)
            ->whereNotNull('numero_control')
            ->max('numero_control');

        $secuencia = $ultimo ? ((int) substr($ultimo, -15)) + 1 : 1;

        return sprintf(
            'DTE-%s-%s%03dP%03d-%015d',
            $tipoDocumento,
            $letra,
            (int) $emisor->codigo_establecimiento,
            (int) ($emisor->cod_punto_venta ?? 0),
            $secuencia
        );
    }
}