<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración DE TENANT: corre automáticamente en tenant_{id} al crear
 * el tenant (hook TenantCreated). No toca facturador_central.
 *
 * Nota: cache, sesiones y colas irán en Redis según arquitectura,
 * así que NO creamos esas tablas en la BD del tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};