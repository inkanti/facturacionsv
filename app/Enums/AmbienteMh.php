<?php

namespace App\Enums;

/**
 * Ambiente de destino de los DTE según el MH:
 * '00' = pruebas, '01' = producción.
 */
enum AmbienteMh: string
{
    case Pruebas = '00';
    case Produccion = '01';

    /** Etiqueta legible para pantallas. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Pruebas => 'Pruebas',
            self::Produccion => 'Producción',
        };
    }
}