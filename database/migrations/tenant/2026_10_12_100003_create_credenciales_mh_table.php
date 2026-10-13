<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credenciales de autenticación ante el MH (NIT + contraseña del
 * servicio de autenticación del Ministerio de Hacienda).
 *
 * El token JWT que devuelve el MH (~24h) NO se guarda aquí: se
 * cachea en Redis cuando implementemos la integración real (módulo 6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credenciales_mh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emisor_id')
                ->unique()          // unas credenciales por emisor
                ->constrained('emisores')
                ->cascadeOnDelete();

            $table->string('nit', 17);
            $table->string('password'); // Cifrada vía cast "encrypted"
            $table->string('estado', 20)->default('activo');

            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credenciales_mh');
    }
};