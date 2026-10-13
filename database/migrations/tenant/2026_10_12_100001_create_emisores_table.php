<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos del emisor (contribuyente) del tenant.
 * Un tenant = un negocio = un emisor (en esta fase).
 *
 * Nota: campos de catálogo del MH (actividad_economica,
 * tipo_establecimiento) se guardan como código; la validación
 * contra el catálogo oficial se agrega cuando tengamos la
 * documentación del MH.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emisores', function (Blueprint $table) {
            $table->id();

            // Identificación fiscal
            $table->string('nit', 17)->unique();       // Formato MH: #########-#
            $table->string('nrc', 20)->nullable();     // Núm. Registro de Contribuyente

            // Nombres
            $table->string('nombre_razon_social', 200);
            $table->string('nombre_comercial', 150)->nullable();

            // Catálogos MH (códigos, se validan cuando tengamos los catálogos)
            $table->string('actividad_economica', 10); // Código actividad económica MH
            $table->string('actividad_economica_desc', 255)->nullable();
            $table->string('tipo_establecimiento', 2); // Catálogo tipos de establecimiento MH
            $table->string('codigo_establecimiento', 4); // Ej. "0001" (casa matriz)

            // Contacto / ubicación del establecimiento
            $table->string('direccion', 255);
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 120)->nullable();

            // Ambiente MH: '00' = pruebas, '01' = producción
            $table->char('ambiente', 2)->default('00');

            $table->string('estado', 20)->default('activo'); // activo|inactivo

            $table->timestamps();

            // Índices: estado y campos de búsqueda (regla de arquitectura)
            $table->index('estado');
            $table->index('nombre_razon_social');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emisores');
    }
};