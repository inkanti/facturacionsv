<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 5 — Usuarios administradores de la plataforma (BD central).
 * Autenticación separada de los users de cada tenant (guard "admin").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('email', 190)->unique();
            $table->string('password');
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamp('ultimo_acceso_en')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};