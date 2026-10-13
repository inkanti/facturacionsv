<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot completo del receptor al emitir (el generador lo lee de acá)
        Schema::table('documentos', function (Blueprint $table) {
            $table->json('receptor_snapshot')->nullable()->after('receptor_correo');
        });

        // Snapshots de catálogo MH en el detalle (sobreviven al borrado del producto)
        Schema::table('documento_detalles', function (Blueprint $table) {
            $table->unsignedTinyInteger('tipo_item')->default(1)->after('nombre');   // CAT-011
            $table->unsignedTinyInteger('unidad_medida')->default(59)->after('tipo_item'); // CAT-014
        });
    }

    public function down(): void
    {
        Schema::table('documentos', fn (Blueprint $t) => $t->dropColumn('receptor_snapshot'));
        Schema::table('documento_detalles', fn (Blueprint $t) => $t->dropColumn(['tipo_item', 'unidad_medida']));
    }
};