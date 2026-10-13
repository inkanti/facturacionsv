<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()
                ->constrained('productos')->nullOnDelete();
            // Snapshot del producto (sobrevive a cambios/borrados del catálogo)
            $table->string('codigo', 30);
            $table->string('nombre', 150);
            $table->decimal('cantidad', 10, 3);
            $table->decimal('precio_unitario', 12, 2);
            $table->string('tipo_iva', 20);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index('documento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_detalles');
    }
};