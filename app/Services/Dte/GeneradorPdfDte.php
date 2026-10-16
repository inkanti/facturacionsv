<?php

namespace App\Services\Dte;

use App\Models\Documento;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Módulo 10 — Genera la representación gráfica (PDF) de un DTE.
 * Replica la lógica de DocumentoController::pdf() para poder usarla
 * desde la cola (el job de envío al cliente).
 *
 * OJO: verifica dos líneas contra tu controlador existente:
 *  1. La fecha de emisión para el QR (aquí se usa created_at; si tienes
 *     columna propia tipo fecha_emision, úsala).
 *  2. El nombre del método de NumeroALetras (aquí se asume enLetras()).
 */
class GeneradorPdfDte
{
    public function generar(Documento $documento): string
    {
        $pdf = Pdf::loadView('tenant.documentos.pdf', [
            'documento'   => $documento,
            'qr'          => $this->qrConsultaPublica($documento),
            'totalLetras' => app(NumeroALetras::class)->aLetras((float) $documento->total),
        ]);

        $pdf->setPaper('letter');

        return $pdf->output(); // bytes crudos del PDF (para attachData)
    }

    /**
     * QR de consulta pública del MH (Manual Técnico 2.0).
     * chillerlan v5: render() ya devuelve data URI completo (convención 15).
     */
    private function qrConsultaPublica(Documento $documento): string
    {
        $url = sprintf(
            'https://admin.factura.gob.sv/consultaPublica?ambiente=%s&codGen=%s&fechaEmi=%s',
            config('dte.ambiente', '00'),
            $documento->codigo_generacion,
            $documento->created_at->format('Y-m-d')
        );

        $opciones = new QROptions([
            'outputType'   => 'png',
            'scale'        => 5,
            'outputBase64' => true,
        ]);

        return (new QRCode($opciones))->render($url);
    }
}