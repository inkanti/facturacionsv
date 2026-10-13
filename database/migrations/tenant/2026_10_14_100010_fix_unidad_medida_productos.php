<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CAT-014: códigos enteros 1-99 (59=Unidad, 99=Otra)
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedTinyInteger('unidad_medida')->default(59)->change();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('unidad_medida', 2)->default('59')->change();
        });
    }
};

