<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos requeridos por el schema v4 del MH a "emisores",
 * saltando los que ya existan (p. ej. "nrc" ya venía del Módulo 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emisores', function (Blueprint $table) {
            if (!Schema::hasColumn('emisores', 'nrc')) {
                $table->string('nrc', 8)->nullable()->after('nit');
            }
            if (!Schema::hasColumn('emisores', 'distrito')) {
                $table->string('distrito', 2)->nullable();
            }
            if (!Schema::hasColumn('emisores', 'desc_actividad')) {
                $table->string('desc_actividad', 150)->nullable();
            }
            if (!Schema::hasColumn('emisores', 'cod_estable')) {
                $table->string('cod_estable', 4)->nullable();
            }
            if (!Schema::hasColumn('emisores', 'cod_punto_venta')) {
                $table->string('cod_punto_venta', 15)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('emisores', function (Blueprint $table) {
            foreach (['nrc', 'distrito', 'desc_actividad', 'cod_estable', 'cod_punto_venta'] as $columna) {
                if (Schema::hasColumn('emisores', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};