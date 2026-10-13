<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de productos y servicios.
 * precio_unitario = precio base SIN IVA (el IVA se calcula al emitir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->string('descripcion', 500)->nullable();
            $table->string('tipo', 1)->default('1')->comment('1=Bien, 2=Servicio');
            $table->string('unidad_medida', 2)->default('99')
                ->comment('Código MH de unidad de medida');
            $table->decimal('precio_unitario', 12, 2);
            // gravado / exento / excluido (cast al enum TipoIva en el modelo)
            $table->string('tipo_iva', 20)->default('gravado');
            $table->decimal('porcentaje_iva', 4, 2)->default(13.00);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
            $table->index('tipo_iva');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};