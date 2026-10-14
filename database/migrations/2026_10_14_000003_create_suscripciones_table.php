<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 5 — Suscripciones de cada tenant (BD central).
 * No hay llave foránea a tenants: los tenants huérfanos se limpian por SQL directo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 36)->index();
            $table->foreignId('plan_id')->nullable()->constrained('planes')->nullOnDelete();
            $table->string('estado', 20)->default('trial')->index();
            $table->date('inicia_en')->nullable();
            $table->date('vence_en')->nullable();
            $table->date('trial_hasta')->nullable();
            $table->timestamp('cancelado_en')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};