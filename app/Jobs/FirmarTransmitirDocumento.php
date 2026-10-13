<?php

namespace App\Jobs;

use App\Contracts\ProveedorDte;
use App\Enums\EstadoDocumento;
use App\Exceptions\FallaTransmisionException;
use App\Models\Documento;
use App\Models\Tenant;
use App\Services\Dte\AlmacenDte;
use App\Services\Dte\GeneradorJsonDte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FirmarTransmitirDocumento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        public int $documentoId,
        public string $tenantId,
    ) {}

    public function handle(
        ProveedorDte $proveedor,
        GeneradorJsonDte $generador,
        AlmacenDte $almacen,
    ): void {
        // El job corre en contexto central: inicializamos el tenant explícitamente
        tenancy()->initialize(Tenant::find($this->tenantId));

        try {
            $documento = Documento::findOrFail($this->documentoId);

            if ($documento->estado !== EstadoDocumento::Borrador) {
                return; // ya fue procesado (ej. reintento de la cola)
            }

            // 1. JSON + copia en disco
            $json = $generador->generar($documento);
            $rutaJson = $almacen->guardarJson($documento, $json);

            // 2. Firma + copia en disco
            $firma = $proveedor->firmar($json);
            $rutaFirma = $almacen->guardarFirma($documento, $firma);

            $documento->update([
                'estado' => EstadoDocumento::Firmado,
                'json_dte' => json_decode($json, true),
                'ruta_json' => $rutaJson,
                'firma_jws' => $firma,
                'ruta_firma' => $rutaFirma,
            ]);

            // 3. Transmisión
            $documento->update(['estado' => EstadoDocumento::Transmitido]);
            $respuesta = $proveedor->transmitir($firma, json_decode($json, true));

            $documento->update([
                'estado' => $respuesta->aceptado
                    ? EstadoDocumento::Validado
                    : EstadoDocumento::Rechazado,
                'respuesta_mh' => $respuesta->toArray(),
            ]);
        } catch (FallaTransmisionException $e) {
            // Sin conectividad con el MH: contingencia + reintento programado
            Documento::whereKey($this->documentoId)->update([
                'estado' => EstadoDocumento::Contingencia,
            ]);

            Log::warning("DTE {$this->documentoId} en contingencia: {$e->getMessage()}");

            ReintentarTransmision::dispatch($this->documentoId, $this->tenantId)
                ->onQueue('dte')
                ->delay(now()->addMinutes(30));
        } finally {
            tenancy()->end();
        }
    }

    /** Agotados los reintentos de la cola: dejar en contingencia para ReintentarTransmision. */
    public function failed(\Throwable $exception): void
    {
        tenancy()->initialize(Tenant::find($this->tenantId));

        try {
            Documento::whereKey($this->documentoId)
                ->where('estado', '!=', EstadoDocumento::Validado->value)
                ->update(['estado' => EstadoDocumento::Contingencia]);

            ReintentarTransmision::dispatch($this->documentoId, $this->tenantId)
                ->onQueue('dte')
                ->delay(now()->addMinutes(30));
        } finally {
            tenancy()->end();
        }
    }
}