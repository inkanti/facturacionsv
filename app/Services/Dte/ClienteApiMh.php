<?php

namespace App\Services\Dte;

use App\Enums\AmbienteMh;
use App\Exceptions\FallaTransmisionException;
use App\Models\CredencialMh;
use App\Models\Emisor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Cliente del API del MH: autenticación + helpers HTTP (Manual Técnico v2.0). */
class ClienteApiMh
{
    /** Token Bearer cacheado. Vigencia MH: 48 h pruebas / 24 h producción → margen de 2 h. */
    public function token(): string
    {
        $emisor = Emisor::activo()->firstOrFail();
        $credencial = CredencialMh::firstOrFail();

        $ttlHoras = $emisor->ambiente === AmbienteMh::Pruebas ? 46 : 22;
        $cacheKey = 'mh:token:' . tenant('id') . ':' . $emisor->nit;

        return Cache::remember($cacheKey, now()->addHours($ttlHoras), function () use ($credencial) {
            $response = Http::asForm()
                ->withUserAgent(config('dte.user_agent'))
                ->timeout(30)
                ->post(config('dte.base_url') . '/seguridad/auth', [
                    'user' => $credencial->nit,
                    'pwd' => $credencial->password, // cast encrypted del modelo
                ]);

            if ($response->failed() || $response->json('status') !== 'OK') {
                // 100-111: error de credenciales/configuración, no de conectividad
                throw new RuntimeException(
                    'Autenticación MH fallida: ' . ($response->json('message') ?? $response->body())
                );
            }

            return $response->json('body.token'); // ya incluye "Bearer "
        });
    }

    public function post(string $ruta, array $body): array
    {
        try {
            $response = Http::withHeaders([
                    'Authorization' => $this->token(),
                    'User-Agent' => config('dte.user_agent'),
                ])
                ->timeout(60)
                ->post(config('dte.base_url') . $ruta, $body);
        } catch (ConnectionException $e) {
            throw new FallaTransmisionException('Sin conectividad con el MH: ' . $e->getMessage());
        }

        return $response->json() ?? [];
    }
}