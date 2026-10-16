<?php

namespace App\Mail;

use App\Models\Documento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Módulo 10 — Correo que se envía al receptor cuando su documento
 * queda validado ante el MH. Adjunta la representación gráfica (PDF)
 * y el JSON oficial del DTE.
 */
class DocumentoValidado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Documento $documento,
        protected string $jsonOficial,
        protected string $pdfBytes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Documento tributario %s', $this->documento->numero_control),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.documento-validado',
        );
    }

    public function attachments(): array
    {
        $base = $this->documento->numero_control;

        return [
            Attachment::fromData(fn () => $this->pdfBytes, $base . '.pdf')
                ->withMime('application/pdf'),
            Attachment::fromData(fn () => $this->jsonOficial, $base . '.json')
                ->withMime('application/json'),
        ];
    }
}