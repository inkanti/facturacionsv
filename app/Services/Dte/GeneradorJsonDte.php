<?php

namespace App\Services\Dte;

use App\Enums\TipoIva;
use App\Models\Documento;
use App\Models\Emisor;

/**
 * Construye el JSON del DTE según los JSON Schema oficiales del MH.
 * Soporta: Factura FE (01, schema v2), CCF (03, schema v4),
 * Nota de crédito (05) y Nota de débito (06), ambas schema v4.
 * Basado en: fe-f-v2.json, fe-ccf-v4.json, fe-nc-v4.json, fe-nd-v4.json
 * + Catálogos del MH.
 *
 * Diferencias clave entre schemas:
 * - identificacion: las notas exigen "fusion"; la FE va en version 2.
 * - emisor: la CCF lleva codEstable/codPuntoVenta; las notas lo PROHÍBEN.
 * - receptor: FE y notas usan tipoDocumento/numDocumento; la CCF usa "nit".
 * - cuerpo: la FE exige ivaItem; las notas exigen numeroDocumento + totalIva
 *   por línea y PROHÍBEN psv.
 * - resumen: cada tipo tiene su propio shape (ver resumen()).
 */
class GeneradorJsonDte
{
    public function generar(Documento $documento): string
    {
        $documento->loadMissing('detalles', 'receptor', 'padre');
        $emisor = Emisor::activo()->firstOrFail();
        $receptor = $this->datosReceptor($documento);

        $json = [
            'identificacion' => $this->identificacion($documento, $emisor),
            'documentoRelacionado' => $this->documentoRelacionado($documento),
            'emisor' => $this->emisor($emisor, $this->esNota($documento)),
            'receptor' => $this->receptor($receptor, $documento->tipo_documento),
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => $this->cuerpo($documento),
            'resumen' => $this->resumen($documento),
            'apendice' => null,
        ];

        // Las notas (05/06) NO tienen el bloque otrosDocumentos en su schema:
        // enviarlo (aunque sea null) viola additionalProperties: false.
        if ($this->esNota($documento)) {
            unset($json['otrosDocumentos']);
        }

        return json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Snapshot del receptor guardado en el documento; si está vacío
     * (documentos viejos), responde del receptor vivo como respaldo.
     */
    private function datosReceptor(Documento $documento): array
    {
        $snapshot = $documento->receptor_snapshot ?? [];

        if (! empty($snapshot)) {
            return $snapshot;
        }

        return $documento->receptor?->only([
            'tipo_documento', 'num_documento', 'nrc', 'nombre', 'nombre_comercial',
            'actividad_economica', 'desc_actividad', 'telefono', 'correo',
            'departamento', 'municipio', 'distrito', 'direccion_complemento',
        ]) ?? [];
    }

    /** La Factura Electrónica (01) usa el schema v2; el resto de los que emitimos, v4. */
    private function esFe(Documento $documento): bool
    {
        return $documento->tipo_documento === '01';
    }

    /** Nota de crédito (05) o nota de débito (06): llevan documentoRelacionado. */
    private function esNota(Documento $documento): bool
    {
        return in_array($documento->tipo_documento, ['05', '06'], true);
    }

    private function identificacion(Documento $documento, Emisor $emisor): array
    {
        $identificacion = [
            'version' => $this->esFe($documento) ? 2 : 4, // const de cada schema
            'ambiente' => $emisor->ambiente->value, // AmbienteMh: 00 pruebas / 01 producción
            'tipoDte' => $documento->tipo_documento,
            'numeroControl' => $documento->numero_control,
            'codigoGeneracion' => $documento->codigo_generacion,
            'tipoModelo' => 1,          // CAT-003: facturación previo
            'tipoOperacion' => 1,       // CAT-004: transmisión normal (2 = contingencia)
            'tipoContingencia' => null, // CAT-005
            'motivoContin' => null,
            'fecEmi' => $documento->fecha_emision->format('Y-m-d'),
            'horEmi' => $documento->fecha_emision->format('H:i:s'),
            'tipoMoneda' => 'USD',
        ];

        // Solo las notas (05/06) lo exigen (type ['string','null'])
        if ($this->esNota($documento)) {
            $identificacion['fusion'] = null;
        }

        return $identificacion;
    }

    /**
     * Solo las notas (05/06) lo llevan: referencia al documento que afectan.
     * tipoGeneracion: 1 = físico, 2 = electrónico (CAT-017).
     */
    private function documentoRelacionado(Documento $documento): ?array
    {
        if (! $this->esNota($documento)) {
            return null;
        }

        $padre = $documento->padre;

        return [[
            'tipoDocumento' => $padre->tipo_documento,
            'tipoGeneracion' => 2, // electrónico
            'numeroDocumento' => $padre->numero_control,
            'fechaEmision' => $padre->fecha_emision->format('Y-m-d'),
        ]];
    }

    /**
     * El emisor de la CCF lleva codEstable/codPuntoVenta; el de las notas
     * NO (additionalProperties: false).
     */
    private function emisor(Emisor $e, bool $esNota): array
    {
        $emisor = [
            'nit' => $e->nit,
            'nrc' => $e->nrc,
            'nombre' => $e->nombre_razon_social,
            'codActividad' => $e->actividad_economica,
            'descActividad' => $e->actividad_economica_desc,
            'nombreComercial' => $e->nombre_comercial ?? null,
            'direccion' => [
                'departamento' => $e->departamento,
                'municipio' => $e->municipio,
                'distrito' => $e->distrito,
                'complemento' => $e->direccion_complemento ?? $e->direccion,
            ],
            'telefono' => $e->telefono,
            'correo' => $e->correo,
        ];

        if (! $esNota) {
            $emisor['codEstable'] = $e->codigo_establecimiento;
            $emisor['codPuntoVenta'] = $e->cod_punto_venta;
        }

        return $emisor;
    }

    /**
     * El shape del receptor CAMBIA según el documento:
     * - CCF v4: nit, nrc, nombre..., nombreComercial.
     * - FE v2 y notas (05/06): tipoDocumento (CAT-022), numDocumento, nrc...
     */
    private function receptor(array $r, string $tipoDocumento): ?array
    {
        if ($tipoDocumento === '01') {
            return $this->receptorFe($r);
        }

        if (in_array($tipoDocumento, ['05', '06'], true)) {
            return $this->receptorNota($r);
        }

        // CCF v4: receptor con NIT, NRC, actividad y dirección completos
        return [
            'nit' => $r['num_documento'] ?? null,
            'nrc' => $r['nrc'] ?? null,
            'nombre' => $r['nombre'] ?? null,
            'codActividad' => $r['actividad_economica'] ?? null,
            'descActividad' => $r['desc_actividad'] ?? null,
            'nombreComercial' => $r['nombre_comercial'] ?? null,
            'direccion' => [
                'departamento' => $r['departamento'] ?? null,
                'municipio' => $r['municipio'] ?? null,
                'distrito' => $r['distrito'] ?? null,
                'complemento' => $r['direccion_complemento'] ?? null,
            ],
            'telefono' => $r['telefono'] ?? null,
            'correo' => $r['correo'] ?? null,
        ];
    }

    /**
     * Receptor de notas (05/06, schema v4): igual de exigente que el de la
     * CCF pero identificado con tipoDocumento/numDocumento (no "nit") y con
     * direccion completa requerida (subcampos no nulos).
     */
    private function receptorNota(array $r): array
    {
        return [
            'tipoDocumento' => $r['tipo_documento'] ?? null, // CAT-022, requerido
            'numDocumento' => $r['num_documento'] ?? null,   // requerido, max 20
            'nrc' => $r['nrc'] ?? null,                      // requerido
            'nombre' => $r['nombre'] ?? null,                // requerido
            'codActividad' => $r['actividad_economica'] ?? null,
            'descActividad' => $r['desc_actividad'] ?? null,
            'nombreComercial' => $r['nombre_comercial'] ?? null,
            'direccion' => [
                'departamento' => $r['departamento'] ?? '',
                'municipio' => $r['municipio'] ?? '',
                'distrito' => $r['distrito'] ?? '',
                'complemento' => $r['direccion_complemento'] ?? '',
            ],
            'telefono' => $r['telefono'] ?? null,
            'correo' => $r['correo'] ?? null,
        ];
    }

    /**
     * Receptor según fe-f-v2.json. Todos los campos son anulables y el bloque
     * completo puede ser null (consumidor final sin datos).
     * OJO: si "direccion" va presente, sus campos NO son anulables en el schema
     * (complemento exige minLength 1) -> solo se envía si está completa.
     */
    private function receptorFe(array $r): ?array
    {
        $tieneDireccion = ! empty($r['departamento']) && ! empty($r['direccion_complemento']);

        return [
            'tipoDocumento' => $r['tipo_documento'] ?? null, // CAT-022
            'numDocumento' => $r['num_documento'] ?? null,
            'nrc' => $r['nrc'] ?? null,
            'nombre' => $r['nombre'] ?? null,
            'codActividad' => $r['actividad_economica'] ?? null,
            'descActividad' => $r['desc_actividad'] ?? null,
            'direccion' => $tieneDireccion ? [
                'departamento' => $r['departamento'],
                'municipio' => $r['municipio'] ?? '',
                'distrito' => $r['distrito'] ?? '',
                'complemento' => $r['direccion_complemento'],
            ] : null,
            'telefono' => $r['telefono'] ?? null,
            'correo' => $r['correo'] ?? null,
        ];
    }

    private function cuerpo(Documento $documento): array
    {
        return $documento->detalles->map(function ($d, $i) use ($documento) {
            $item = [
                'numItem' => $i + 1,
                'tipoItem' => (int) $d->tipo_item,
                'numeroDocumento' => $this->esNota($documento)
                    ? $documento->padre->numero_control // exigido por NC/ND
                    : null,
                'codigo' => $d->codigo,
                'codTributo' => null,
                'descripcion' => $d->nombre,
                'cantidad' => (float) $d->cantidad,
                'uniMedida' => (int) $d->unidad_medida, // CAT-014 entero
                'precioUni' => (float) $d->precio_unitario,
                'montoDescu' => 0,
                'ventaNoSuj' => 0,
                'ventaExenta' => $d->tipo_iva !== TipoIva::Gravado ? (float) $d->subtotal : 0,
                'ventaGravada' => $d->tipo_iva === TipoIva::Gravado ? (float) $d->subtotal : 0,
                'tributos' => $d->tipo_iva === TipoIva::Gravado ? ['20'] : null, // 20 = IVA 13% (CAT-015)
                'noGravado' => 0,
            ];

            // La FE v2 exige ivaItem por línea; el CCF v4 lo prohíbe (additionalProperties: false)
            if ($this->esFe($documento)) {
                $item['psv'] = 0;
                $item['ivaItem'] = round((float) $d->subtotal * $d->tipo_iva->porcentaje() / 100, 2);
            } else {
                $item['psv'] = 0;
            }

            // Las notas (05/06) exigen IVA explícito por línea y prohíben psv
            if ($this->esNota($documento)) {
                unset($item['psv']);
                $item['ivaPerci'] = 0;
                $item['totalIva'] = $d->tipo_iva === TipoIva::Gravado
                    ? round((float) $d->iva, 2)
                    : 0;
                $item['ivaRete'] = 0;
            }

            return $item;
        })->values()->all();
    }

    private function resumen(Documento $documento): array
    {
        $iva = (float) $documento->iva;
        $gravada = (float) $documento->detalles->where('tipo_iva', TipoIva::Gravado)->sum('subtotal');
        $exenta = (float) $documento->detalles->where('tipo_iva', '!=', TipoIva::Gravado)->sum('subtotal');

        // Las notas (05/06) tienen su propio shape de resumen: sin descu*, subTotal,
        // saldoFavor ni pagos; con totalIva y codigoRetencionMH.
        if ($this->esNota($documento)) {
            $resumenNota = [
                'totalNoSuj' => 0,
                'totalExenta' => round($exenta, 2),
                'totalGravada' => round($gravada, 2),
                'subTotalVentas' => (float) $documento->subtotal,
                'totalDescu' => 0,
                'tributos' => $iva > 0
                    ? [['codigo' => '20', 'descripcion' => 'Impuesto al Valor Agregado 13%', 'valor' => $iva]]
                    : null,
                'montoTotalOperacion' => (float) $documento->total,
                'ivaPerci' => 0,
                'totalIva' => round($iva, 2),
                'ivaRete' => 0,
                'totalNoGravado' => 0,
                'totalPagar' => (float) $documento->total,
                'totalLetras' => app(NumeroALetras::class)->aLetras((float) $documento->total),
                'condicionOperacion' => 1, // CAT-016: 1 contado, 2 crédito, 3 otro
                'observaciones' => null,
                'codigoRetencionMH' => null,
            ];

            // Solo la ND exige numPagoElectronico
            if ($documento->tipo_documento === '06') {
                $resumenNota['numPagoElectronico'] = null;
            }

            return $resumenNota;
        }

        $resumen = [
            'totalNoSuj' => 0,
            'totalExenta' => round($exenta, 2),
            'totalGravada' => round($gravada, 2),
            'subTotalVentas' => (float) $documento->subtotal,
            'descuNoSuj' => 0,
            'descuExenta' => 0,
            'descuGravada' => 0,
            'porcentajeDescuento' => 0,
            'totalDescu' => 0,
            'tributos' => $iva > 0
                ? [['codigo' => '20', 'descripcion' => 'Impuesto al Valor Agregado 13%', 'valor' => $iva]]
                : null,
            'subTotal' => (float) $documento->subtotal,
            'ivaPerci' => 0,
            'ivaRete' => 0,
            'montoTotalOperacion' => (float) $documento->total,
            'totalNoGravado' => 0,
            'totalPagar' => (float) $documento->total,
            'totalLetras' => app(NumeroALetras::class)->aLetras((float) $documento->total),
            'saldoFavor' => 0,
            'condicionOperacion' => 1, // CAT-016: 1 contado, 2 crédito, 3 otro
            'pagos' => null,           // CAT-017 cuando aplique
            'numPagoElectronico' => null,
            'observaciones' => null,
        ];

        // La FE v2: prohíbe ivaPerci y exige totalIva. El CCF v4: al revés.
        if ($this->esFe($documento)) {
            unset($resumen['ivaPerci']);
            $resumen['totalIva'] = round($iva, 2);
        }

        return $resumen;
    }
}