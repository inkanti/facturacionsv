<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alinea "emisores" con los nombres reales del Módulo 1:
 * - Elimina columnas redundantes agregadas por error (cod_estable, desc_actividad)
 * - Agrega las que el schema v4 exige y no existían
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emisores', function (Blueprint $table) {
            if (Schema::hasColumn('emisores', 'cod_estable')) {
                $table->dropColumn('cod_estable'); // ya existe codigo_establecimiento
            }
            if (Schema::hasColumn('emisores', 'desc_actividad')) {
                $table->dropColumn('desc_actividad'); // ya existe actividad_economica_desc
            }
            if (!Schema::hasColumn('emisores', 'departamento')) {
                $table->string('departamento', 2)->nullable()->after('direccion');
            }
            if (!Schema::hasColumn('emisores', 'municipio')) {
                $table->string('municipio', 2)->nullable()->after('departamento');
            }
            if (!Schema::hasColumn('emisores', 'direccion_complemento')) {
                $table->string('direccion_complemento', 200)->nullable()->after('municipio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('emisores', function (Blueprint $table) {
            $table->string('cod_estable', 4)->nullable();
            $table->string('desc_actividad', 150)->nullable();
            $table->dropColumn(['departamento', 'municipio', 'direccion_complemento']);
        });
    }
};