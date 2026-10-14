<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Módulo 5 — Planes iniciales (central). Idempotente.
 */
class PlanesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            [
                'nombre' => 'Básico', 'slug' => 'basico',
                'descripcion' => 'Para emprendedores que están iniciando su formalización.',
                'precio_mensual' => 9.99, 'limite_emisores' => 1,
                'limite_usuarios' => 2, 'limite_documentos_mes' => 200, 'orden' => 1,
            ],
            [
                'nombre' => 'Profesional', 'slug' => 'profesional',
                'descripcion' => 'Para negocios con varios puntos de venta.',
                'precio_mensual' => 19.99, 'limite_emisores' => 3,
                'limite_usuarios' => 5, 'limite_documentos_mes' => 1000, 'orden' => 2,
            ],
            [
                'nombre' => 'Empresarial', 'slug' => 'empresarial',
                'descripcion' => 'Sin límites, para operaciones de mayor volumen.',
                'precio_mensual' => 39.99, 'limite_emisores' => 10,
                'limite_usuarios' => 20, 'limite_documentos_mes' => null, 'orden' => 3,
            ],
        ];

        foreach ($planes as $p) {
            Plan::firstOrCreate(
                ['slug' => $p['slug']],
                $p + ['es_visible' => true, 'estado' => 'activo']
            );
        }
    }
}