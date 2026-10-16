<?php

namespace App\Jobs;

use App\Enums\EstadoDocumento;
use App\Mail\DocumentoValidado;
use App\Models\Documento;
use App\Models\Tenant;
use App\Services\Dte\GeneradorJsonDte;
use App\Services\Dte\GeneradorPdfDte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Módulo 10 — Envía el PDF + JSON del documento validado al correo
 * del receptor. Despachado desde FirmarTransmitirDocumento al llegar
 * a estado "validado".
 *
 * Convención 13: recibe tenantId e inicializa/finaliza tenancy a mano.
 * Idempotente: si correo_enviado_en ya está lleno, no hace nada.
 */
class EnviarDocumentoCliente implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $tenantId,
        public int $documentoId,
    ) {
        // OJO: no declarar $queue como propiedad tipada — el trait Queueable
        // ya la define sin tipo y PHP las considera incompatibles (fatal error).
        $this->onQueue('dte');
    }

    public function handle(): void
    {
        tenancy()->initialize(Tenant::find($this->tenantId));

        try {
            $documento = Documento::find($this->documentoId);

            if (! $documento || $documento->estado !== EstadoDocumento::Validado) {
                return;
            }

            if ($documento->correo_enviado_en !== null) {
                return; // ya fue enviado
            }

            // Correo del receptor desde el snapshot (convención 6):
            // sobrevive a cambios o borrados del catálogo.
            $correo = data_get($documento->receptor_snapshot, 'correo');

            if (empty($correo)) {
                // Sin correo no hay envío; se registra el intento y se sale sin error.
                $documento->increment('correo_intentos');
                return;
            }

            // JSON oficial (mismo generador que usa el pipeline de emisión)
            $json = app(GeneradorJsonDte::class)->generar($documento);
            $pdf  = app(GeneradorPdfDte::class)->generar($documento);

            Mail::to($correo)->send(new DocumentoValidado($documento, $json, $pdf));

            $documento->update([
                'correo_enviado_en' => now(),
                'correo_intentos'   => $documento->correo_intentos + 1,
            ]);
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Si el job agota sus intentos, se deja constancia en el documento
     * para poder diagnosticar desde el panel.
     */
    public function failed(Throwable $exception): void
    {
        tenancy()->initialize(Tenant::find($this->tenantId));

        try {
            Documento::whereKey($this->documentoId)->increment('correo_intentos');
        } finally {
            tenancy()->end();
        }
    }
}