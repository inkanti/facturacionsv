<?php

namespace App\Services\Dte;

use App\Enums\AmbienteMh;
use App\Enums\TipoIva;
use App\Models\Documento;
use App\Models\Emisor;

/**
 * Construye el JSON del DTE según los JSON Schema oficiales del MH (versión 4).
 * Basado en: fe-ccf-v4.json + Catálogos V1.1 del MH.
 */
class GeneradorJsonDte
{
    public function generar(Documento $documento): string
    {
        $documento->loadMissing('detalles', 'receptor');
        $emisor = Emisor::activo()->firstOrFail();  
        $receptor = $documento->receptor_snapshot ?? [];

        $json = [
            'identificacion' => $this->identificacion($documento, $emisor),
            'documentoRelacionado' => null,
            'emisor' => $this->emisor($emisor),
            'receptor' => $this->receptor($receptor),
            'otrosDocumentos' => null,
            'ventaTercero' => null,
            'cuerpoDocumento' => $this->cuerpo($documento),
            'resumen' => $this->resumen($documento),
            'apendice' => null,
        ];

        return json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    private function identificacion(Documento $documento, Emisor $emisor): array
    {
        return [
            'version' => 4, // const del schema v4
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
    }

  private function emisor(Emisor $e): array
    {
        return [
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
            'codEstable' => $e->codigo_establecimiento,
            'codPuntoVenta' => $e->cod_punto_venta,
        ];
    }

    private function receptor(array $r): array
    {
        // CCF: receptor con NIT, NRC, actividad y dirección completos
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

    private function cuerpo(Documento $documento): array
    {
        return $documento->detalles->map(fn ($d, $i) => [
            'numItem' => $i + 1,
            'tipoItem' => $d->tipo_item,
            'numeroDocumento' => null,
            'codigo' => $d->codigo,
            'codTributo' => null,
            'descripcion' => $d->nombre,
            'cantidad' => (float) $d->cantidad,
            'uniMedida' => $d->unidad_medida, // CAT-014 entero
            'precioUni' => (float) $d->precio_unitario,
            'montoDescu' => 0,
            'ventaNoSuj' => 0,
            'ventaExenta' => $d->tipo_iva !== TipoIva::Gravado ? (float) $d->subtotal : 0,
            'ventaGravada' => $d->tipo_iva === TipoIva::Gravado ? (float) $d->subtotal : 0,
            'tributos' => $d->tipo_iva === TipoIva::Gravado ? ['20'] : null, // 20 = IVA 13% (CAT-015)
            'psv' => 0,
            'noGravado' => 0,
        ])->values()->all();
    }

    private function resumen(Documento $documento): array
    {
        $iva = (float) $documento->iva;
        $gravada = (float) $documento->detalles->where('tipo_iva', TipoIva::Gravado)->sum('subtotal');
        $exenta = (float) $documento->detalles->where('tipo_iva', '!=', TipoIva::Gravado)->sum('subtotal');

        return [
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
            'totalLetras' => null, // TODO: conversor de número a letras
            'saldoFavor' => 0,
            'condicionOperacion' => 1, // CAT-016: 1 contado, 2 crédito, 3 otro
            'pagos' => null,           // CAT-017 cuando aplique
            'numPagoElectronico' => null,
            'observaciones' => null,
        ];
    }
}