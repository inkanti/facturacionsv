<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 10 — Control del envío automático del documento al cliente.
 * correo_enviado_en: sello de tiempo del último envío exitoso (null = no enviado).
 * correo_intentos: cuántas veces se intentó enviar (útil para diagnóstico).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('documentos', 'correo_enviado_en')) {
            Schema::table('documentos', function (Blueprint $table) {
                $table->timestamp('correo_enviado_en')->nullable()->after('respuesta_mh');
                $table->unsignedSmallInteger('correo_intentos')->default(0)->after('correo_enviado_en');
                $table->index('correo_enviado_en');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('documentos', 'correo_enviado_en')) {
            Schema::table('documentos', function (Blueprint $table) {
                $table->dropIndex(['correo_enviado_en']);
                $table->dropColumn(['correo_enviado_en', 'correo_intentos']);
            });
        }
    }
};