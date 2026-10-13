<?php

namespace App\Services\Dte;

use App\Contracts\ProveedorDte;
use App\Exceptions\FallaTransmisionException;
use App\Models\Documento;

class ProveedorDteSimulado implements ProveedorDte
{
    public function firmar(string $jsonDte): string
    {
        // Dev: JWS compacto de 3 segmentos con huella del JSON.
        // NO es firma criptográfica real; eso lo hace ProveedorDteMH.
        $segmento = fn (string $dato): string => rtrim(strtr(base64_encode($dato), '+/', '-_'), '=');

        $header = $segmento(json_encode(['alg' => 'ES256', 'typ' => 'JWT']));
        $payload = $segmento($jsonDte);
        $firma = $segmento(hash('sha256', $jsonDte, true));

        return "{$header}.{$payload}.{$firma}";
    }

    public function transmitir(string $firmaJws, array $jsonDte, int $idEnvio = 1): RespuestaMh
    {
        if (config('dte.simular_contingencia')) {
            throw new FallaTransmisionException('Simulación: servicio del MH no disponible');
        }

        return new RespuestaMh(
            aceptado: true,
            estado: 'PROCESADO',
            observaciones: ['Documento procesado correctamente (proveedor simulado)'],
            raw: [
                'simulado' => true,
                'selloRecibido' => 'SIM-' . substr(md5($firmaJws), 0, 16),
                'fhProcesamiento' => now()->format('d/m/Y H:i:s'),
            ],
        );
    }

    public function consultar(string $nitEmisor, string $tipoDte, string $codigoGeneracion): array
    {
        $documento = Documento::where('codigo_generacion', $codigoGeneracion)->firstOrFail();
        return $documento->respuesta_mh ?? ['estado' => $documento->estado->value, 'simulado' => true];
    }
}