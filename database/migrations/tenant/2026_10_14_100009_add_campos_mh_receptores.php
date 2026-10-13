<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptores', function (Blueprint $table) {
            $table->string('nrc', 8)->nullable()->after('num_documento');        // NRC (obligatorio en CCF)
            $table->string('desc_actividad', 150)->nullable()->after('actividad_economica');
            $table->string('distrito', 2)->nullable()->after('municipio');       // CAT-008
        });
    }

    public function down(): void
    {
        Schema::table('receptores', fn (Blueprint $t) => $t->dropColumn(['nrc', 'desc_actividad', 'distrito']));
    }
};