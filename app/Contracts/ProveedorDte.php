<?php

namespace App\Contracts;

use App\Services\Dte\RespuestaMh;

/**
 * Única puerta de salida hacia el MH.
 * Implementaciones: ProveedorDteSimulado (dev), ProveedorDteMH (producción).
 */
interface ProveedorDte
{
    /** Firma el JSON del DTE. Devuelve JWS compacto. */
    public function firmar(string $jsonDte): string;

    /** Transmite el JWS al MH y devuelve su respuesta. */
    public function transmitir(string $firmaJws, array $jsonDte, int $idEnvio): RespuestaMh;

    /** Consulta un DTE en el MH (servicio consultadte). */
    public function consultar(string $nitEmisor, string $tipoDte, string $codigoGeneracion): array;
}