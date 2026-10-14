<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 5 — Planes de suscripción (BD central).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('slug', 60)->unique();
            $table->text('descripcion')->nullable();
            $table->decimal('precio_mensual', 12, 2)->default(0);
            $table->unsignedSmallInteger('limite_emisores')->default(1);
            $table->unsignedSmallInteger('limite_usuarios')->default(1);
            $table->unsignedInteger('limite_documentos_mes')->nullable(); // null = ilimitado
            $table->json('caracteristicas')->nullable();
            $table->boolean('es_visible')->default(true);
            $table->string('estado', 20)->default('activo')->index();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};