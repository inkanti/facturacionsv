<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos oficiales del MH (CAT-001 a CAT-033) en la BD CENTRAL.
 * OJO: CAT-013 y CAT-008 tienen códigos REPETIDOS entre departamentos ->
 * el padre (código CAT-012) forma parte de la clave única.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mh_catalogos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 8);              // 'CAT-013'
            $table->string('codigo', 8);            // código dentro del catálogo
            $table->string('valor', 255);           // descripción oficial
            $table->string('padre', 8)->nullable(); // departamento para CAT-013/CAT-008
            $table->timestamps();

            $table->unique(['tipo', 'padre', 'codigo']);
            $table->index(['tipo', 'padre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mh_catalogos');
    }
};