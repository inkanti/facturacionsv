<?php

namespace App\Console\Commands;

use App\Enums\EstadoSuscripcion;
use App\Models\Subscription;
use Illuminate\Console\Command;

/**
 * Módulo 5 — Marca como vencidas las suscripciones activas/trial cuya fecha ya pasó.
 * Pensado para correr una vez al día (scheduler).
 */
class ProcesarSuscripcionesVencidas extends Command
{
    protected $signature = 'suscripciones:procesar-vencidas';

    protected $description = 'Marca como vencidas las suscripciones cuya fecha de vencimiento ya pasó';

    public function handle(): int
    {
        $hoy = now()->toDateString();

        $activas = Subscription::where('estado', EstadoSuscripcion::Activa)
            ->whereNotNull('vence_en')
            ->where('vence_en', '<', $hoy)
            ->get();

        foreach ($activas as $suscripcion) {
            $suscripcion->update(['estado' => EstadoSuscripcion::Vencida]);
            $this->line("Vencida suscripción #{$suscripcion->id} (tenant {$suscripcion->tenant_id})");
        }

        $trials = Subscription::where('estado', EstadoSuscripcion::Trial)
            ->whereNotNull('trial_hasta')
            ->where('trial_hasta', '<', $hoy)
            ->get();

        foreach ($trials as $suscripcion) {
            $suscripcion->update(['estado' => EstadoSuscripcion::Vencida]);
            $this->line("Vencida prueba #{$suscripcion->id} (tenant {$suscripcion->tenant_id})");
        }

        $total = $activas->count() + $trials->count();
        $this->info("Listo: {$total} suscripción(es) marcada(s) como vencidas.");

        return self::SUCCESS;
    }
}