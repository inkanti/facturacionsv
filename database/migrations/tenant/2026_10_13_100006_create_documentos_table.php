<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_documento', 2)->default('01'); // catálogo MH
            // Se asignan AL EMITIR, no al crear el borrador (convención 7)
            $table->string('numero_control', 36)->nullable()->unique();
            $table->uuid('codigo_generacion')->nullable()->unique();
            $table->foreignId('receptor_id')->nullable()
                ->constrained('receptores')->nullOnDelete();
            // Snapshot del receptor al momento de la emisión (convención 6)
            $table->string('receptor_tipo_documento', 2)->nullable();
            $table->string('receptor_num_documento', 20)->nullable();
            $table->string('receptor_nombre', 200);
            $table->string('receptor_correo', 100)->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('json_dte')->nullable();       // JSON enviado al MH
            $table->string('ruta_json', 500)->nullable();  // copia en disco (retención 10 años)
            $table->string('firma_jws', 4000)->nullable();
            $table->string('ruta_firma', 500)->nullable();
            $table->json('respuesta_mh')->nullable();   // estado/observaciones del MH
            $table->unsignedSmallInteger('reintentos')->default(0); // transmisiones en contingencia
            $table->timestamp('fecha_emision')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('numero_control');
            $table->index('codigo_generacion');
            $table->index('receptor_nombre');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};