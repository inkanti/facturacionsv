<?php

namespace App\Jobs;

use App\Contracts\ProveedorDte;
use App\Enums\EstadoDocumento;
use App\Exceptions\FallaTransmisionException;
use App\Models\Documento;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** Reintenta la transmisión de un documento en contingencia (backoff exponencial). */
class ReintentarTransmision implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tope: ~48 reintentos de 30 min ≈ 24 horas en contingencia. */
    private const MAX_REINTENTOS = 48;

    public function __construct(
        public int $documentoId,
        public string $tenantId,
    ) {}

    public function handle(ProveedorDte $proveedor): void
    {
        tenancy()->initialize(Tenant::find($this->tenantId));

        try {
            $documento = Documento::findOrFail($this->documentoId);

            if ($documento->estado !== EstadoDocumento::Contingencia) {
                return;
            }

            $documento->increment('reintentos');

            if ($documento->reintentos > self::MAX_REINTENTOS) {
                Log::error("DTE {$documento->id}: agotados los reintentos de contingencia.");
                return; // queda en contingencia para intervención manual
            }

            $respuesta = $proveedor->transmitir(
                $documento->firma_jws,
                $documento->json_dte ?? [],
            );

            $documento->update([
                'estado' => $respuesta->aceptado
                    ? EstadoDocumento::Validado
                    : EstadoDocumento::Rechazado,
                'respuesta_mh' => $respuesta->toArray(),
            ]);
        } catch (FallaTransmisionException $e) {
            // Sigue sin haber conectividad: reprogramar con backoff exponencial (tope 4 h)
            $delay = min(30 * (2 ** min($documento->reintentos, 8)), 240);

            Log::info("DTE {$this->documentoId}: reintento en {$delay} min.");

            self::dispatch($this->documentoId, $this->tenantId)
                ->onQueue('dte')
                ->delay(now()->addMinutes($delay));
        } finally {
            tenancy()->end();
        }
    }
}