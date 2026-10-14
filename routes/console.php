<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use App\Console\Commands\ProcesarSuscripcionesVencidas;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ProcesarSuscripcionesVencidas::class)->dailyAt('00:30');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
