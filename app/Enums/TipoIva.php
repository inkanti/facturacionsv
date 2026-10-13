<?php

namespace App\Enums;

/**
 * Tratamiento de IVA de un producto/servicio.
 * gravado: paga IVA (13% estándar en SV).
 * exento / excluido: sin IVA, pero el MH los distingue en el JSON del DTE.
 */
enum TipoIva: string
{
    case Gravado = 'gravado';
    case Exento = 'exento';
    case Excluido = 'excluido';

    public function porcentaje(): float
    {
        return match ($this) {
            self::Gravado => 13.00,
            self::Exento, self::Excluido => 0.00,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Gravado => 'Gravado (13%)',
            self::Exento => 'Exento',
            self::Excluido => 'Excluido',
        };
    }
}