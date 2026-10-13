<?php

namespace App\Enums;

/**
 * Ciclo de vida del documento (convención 8):
 * borrador → firmado → transmitido → validado / rechazado / contingencia
 */
enum EstadoDocumento: string
{
    case Borrador = 'borrador';
    case Firmado = 'firmado';
    case Transmitido = 'transmitido';
    case Validado = 'validado';
    case Rechazado = 'rechazado';
    case Contingencia = 'contingencia';

    /** Transiciones permitidas desde el estado actual. */
    public function puedePasarA(EstadoDocumento $destino): bool
    {
        return in_array($destino, match ($this) {
            self::Borrador => [self::Firmado],
            self::Firmado => [self::Transmitido],
            self::Transmitido => [self::Validado, self::Rechazado, self::Contingencia],
            // Contingencia reintenta transmisión: puede volver a transmitido o cerrar
            self::Contingencia => [self::Transmitido, self::Rechazado],
            // Estados finales
            default => [],
        });
    }

    public function esFinal(): bool
    {
        return in_array($this, [self::Validado, self::Rechazado]);
    }
}