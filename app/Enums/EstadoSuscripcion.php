<?php

namespace App\Enums;

enum EstadoSuscripcion: string
{
    case Trial = 'trial';
    case Activa = 'activa';
    case Vencida = 'vencida';
    case Suspendida = 'suspendida';
    case Cancelada = 'cancelada';

    /** ¿Permite que el negocio use el sistema? */
    public function permiteAcceso(): bool
    {
        return match ($this) {
            self::Trial, self::Activa => true,
            default => false,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Trial => 'Prueba gratis',
            self::Activa => 'Activa',
            self::Vencida => 'Vencida',
            self::Suspendida => 'Suspendida',
            self::Cancelada => 'Cancelada',
        };
    }
}