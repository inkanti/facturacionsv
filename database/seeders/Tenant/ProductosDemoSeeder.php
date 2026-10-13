<?php

namespace Database\Seeders\Tenant;

use App\Enums\TipoIva;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            [
                'codigo' => 'CAF-001',
                'nombre' => 'Café molido premium 1 lb',
                'descripcion' => 'Café de altura, molido medio',
                'tipo' => '1',
                'unidad_medida' => '99',
                'precio_unitario' => 6.50,
                'tipo_iva' => TipoIva::Gravado,
                'porcentaje_iva' => 13.00,
            ],
            [
                'codigo' => 'PAN-001',
                'nombre' => 'Pan dulce artesanal (unidad)',
                'descripcion' => null,
                'tipo' => '1',
                'unidad_medida' => '99',
                'precio_unitario' => 0.50,
                'tipo_iva' => TipoIva::Exento, // canasta básica
                'porcentaje_iva' => 0.00,
            ],
            [
                'codigo' => 'LEC-001',
                'nombre' => 'Leche entera 1 L',
                'descripcion' => null,
                'tipo' => '1',
                'unidad_medida' => '99',
                'precio_unitario' => 1.20,
                'tipo_iva' => TipoIva::Exento,
                'porcentaje_iva' => 0.00,
            ],
            [
                'codigo' => 'SRV-001',
                'nombre' => 'Servicio de entrega a domicilio',
                'descripcion' => 'Dentro del área metropolitana',
                'tipo' => '2', // servicio
                'unidad_medida' => '99',
                'precio_unitario' => 2.00,
                'tipo_iva' => TipoIva::Gravado,
                'porcentaje_iva' => 13.00,
            ],
            [
                'codigo' => 'MER-001',
                'nombre' => 'Merchandising corporativo (camisa)',
                'descripcion' => null,
                'tipo' => '1',
                'unidad_medida' => '99',
                'precio_unitario' => 8.00,
                'tipo_iva' => TipoIva::Excluido, // ejemplo de ítem excluido
                'porcentaje_iva' => 0.00,
            ],
        ];

        foreach ($productos as $datos) {
            Producto::firstOrCreate(
                ['codigo' => $datos['codigo']],
                [...$datos, 'activo' => true]
            );
        }
    }
}