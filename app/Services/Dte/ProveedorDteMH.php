<?php

namespace App\Services\Dte;

use App\Contracts\ProveedorDte;
use App\Exceptions\FallaTransmisionException;
use App\Models\Emisor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Proveedor real del MH (Manual Técnico v2.0).
 * Firma: servicio local svfe-api-firmador (Docker) provisto por el MH.
 */
class ProveedorDteMH implements ProveedorDte
{
    public function __construct(private ClienteApiMh $cliente) {}

    public function firmar(string $jsonDte): string
    {
        $emisor = Emisor::activo()->firstOrFail();
        // ⚠️ ajustar nombre de relación/columna según el GestorCertificado del Módulo 1
        $certificado = $emisor->certificados()->where('activo', true)->firstOrFail();

        try {
            $response = Http::timeout(60)->post(config('dte.firma_url') . '/firmardocumento/', [
                'nit' => $emisor->nit,
                'active' => true,
                'passwordPri' => $certificado->password, // cast encrypted del modelo
                'dteJson' => json_decode($jsonDte, true),
            ]);
        } catch (ConnectionException $e) {
            // Servicio de firma local caído = contingencia del emisor (CAT-005 tipo 2)
            throw new FallaTransmisionException('Servicio de firma no disponible: ' . $e->getMessage());
        }

        if ($response->failed() || $response->json('status') !== 'OK') {
            throw new RuntimeException(
                'Firma MH fallida: ' . json_encode($response->json('body') ?? $response->body())
            );
        }

        return $response->json('body'); // JWS compacto
    }

    public function transmitir(string $firmaJws, array $jsonDte, int $idEnvio): RespuestaMh
    {
        $emisor = Emisor::activo()->firstOrFail();
        $payload = [
            'ambiente' => $emisor->ambiente->value,
            'idEnvio' => $idEnvio,
            'version' => $jsonDte['identificacion']['version'],
            'tipoDte' => $jsonDte['identificacion']['tipoDte'],
            'documento' => $firmaJws,
            'codigoGeneracion' => $jsonDte['identificacion']['codigoGeneracion'],
        ];

        $resp = $this->cliente->post('/fesv/recepciondte', $payload);

        // 107/108: token inválido → limpiar cache y reintentar una vez
        if (in_array($resp['codigoMsg'] ?? null, ['107', '108'])) {
            Cache::forget('mh:token:' . tenant('id') . ':' . $emisor->nit);
            $resp = $this->cliente->post('/fesv/recepciondte', $payload);
        }

        $estado = $resp['estado'] ?? 'RECHAZADO';

        return new RespuestaMh(
            aceptado: $estado === 'PROCESADO',
            estado: $estado,
            observaciones: array_filter($resp['observaciones'] ?? []),
            raw: $resp,
        );
    }

    /** Servicio 4.3.1 del manual: consulta de DTE en el MH. */
    public function consultar(string $nitEmisor, string $tipoDte, string $codigoGeneracion): array
    {
        return $this->cliente->post('/fesv/recepcion/consultadte', [
            'nitEmisor' => $nitEmisor,
            'tipoDte' => $tipoDte,
            'codigoGeneracion' => $codigoGeneracion,
        ]);
    }
}