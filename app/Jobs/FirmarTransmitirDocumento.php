<?php

namespace App\Jobs;

use App\Contracts\ProveedorDte;
use App\Enums\EstadoDocumento;
use App\Exceptions\FallaTransmisionException;
use App\Models\Documento;
use App\Models\Tenant;
use App\Services\Dte\AlmacenDte;
use App\Services\Dte\GeneradorJsonDte;
use App\Services\Dte\ValidadorDte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

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
        ValidadorDte $validador,
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

            // 1.1 Validación local contra el schema oficial del tipo de DTE.
            // Sin assoc: el validador exige objetos (stdClass), no arreglos.
            // Si falla: rechazo permanente, sin firma ni transmisión.
            try {
                $validador->validar(json_decode($json));
            } catch (InvalidArgumentException $e) {
                $documento->update([
                    'estado' => EstadoDocumento::Rechazado,
                    'respuesta_mh' => ['validacion_local' => $e->getMessage()],
                ]);

                Log::error("DTE {$this->documentoId} rechazado en validación local: {$e->getMessage()}");

                return;
            }

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

            // 4. Envío automático al cliente (Módulo 10): solo si quedó validado.
            // Corre en la misma cola "dte"; el job es idempotente (correo_enviado_en).
            if ($respuesta->aceptado && config('dte.envio_cliente.activo', true)) {
                EnviarDocumentoCliente::dispatch($this->tenantId, $documento->id)
                    ->onQueue('dte');
            }
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