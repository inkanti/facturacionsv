<?php

namespace App\Services\Dte;

use Illuminate\Contracts\Support\Arrayable;

/** Respuesta normalizada del MH (simulado o real). */
readonly class RespuestaMh implements Arrayable
{
    public function __construct(
        public bool $aceptado,
        public string $estado,          // ej. "PROCESADO" / "RECHAZADO"
        public array $observaciones,    // mensajes del MH
        public array $raw,             // respuesta cruta completa
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}