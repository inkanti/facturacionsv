<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metadata de los certificados .p12 del emisor.
 *
 * REGLA DE ARQUITECTURA: el archivo .p12 vive en DISCO, nunca en BD.
 * Aquí solo guardamos la ruta relativa del archivo y la contraseña
 * CIFRADA (cast "encrypted" en el modelo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emisor_id')
                ->constrained('emisores')
                ->cascadeOnDelete();

            // Ubicación en disco (disco "local", aislado por tenant).
            // Guardamos ruta RELATIVA, nunca absoluta.
            $table->string('nombre_archivo', 150);
            $table->string('ruta', 255);

            // Vigencia declarada (la vigencia real se lee del .p12
            // cuando tengamos la firma JWS, módulo 6)
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();

            // Solo un certificado activo por emisor (lo garantiza el servicio)
            $table->boolean('activo')->default(true);

            // Contraseña del .p12 — SIEMPRE cifrada en BD (cast en el modelo)
            $table->string('password');

            $table->timestamps();

            $table->index('activo');
            $table->index('emisor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};