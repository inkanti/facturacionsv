<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega documento_padre_id: vincula una nota de crédito/débito (05/06)
 * con el documento que afecta (03). Sin FK: índice simple, igual que el
 * resto de relaciones del tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documentos') && ! Schema::hasColumn('documentos', 'documento_padre_id')) {
            Schema::table('documentos', function (Blueprint $table) {
                $table->unsignedBigInteger('documento_padre_id')->nullable()->after('id');
                $table->index('documento_padre_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('documentos', 'documento_padre_id')) {
            Schema::table('documentos', function (Blueprint $table) {
                $table->dropIndex(['documento_padre_id']);
                $table->dropColumn('documento_padre_id');
            });
        }
    }
};