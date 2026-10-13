<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de receptores (clientes).
 * Al emitir, el DTE guarda un snapshot; esta tabla es la fuente del catálogo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receptores', function (Blueprint $table) {
            $table->id();
            // Códigos MH tipoDocumento: 36=NIT, 13=DUI, 03=Pasaporte, 02=Carnet residente, 37=Otro
            $table->string('tipo_documento', 2)->default('36');
            // Null = consumidor final (el MH lo permite omitiendo el documento)
            $table->string('num_documento', 20)->nullable();
            $table->string('nombre', 200);
            $table->string('nombre_comercial', 200)->nullable();
            $table->string('actividad_economica', 10)->nullable()
                ->comment('Código MH de actividad económica del receptor');
            $table->string('correo', 100)->nullable();
            $table->string('telefono', 25)->nullable();
            $table->string('departamento', 2)->nullable()->comment('Código MH');
            $table->string('municipio', 2)->nullable()->comment('Código MH');
            $table->string('direccion_complemento', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('num_documento');
            $table->index('nombre');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receptores');
    }
};