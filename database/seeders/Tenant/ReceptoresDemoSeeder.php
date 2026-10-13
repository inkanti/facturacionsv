<?php

namespace Database\Seeders\Tenant;

use App\Models\Receptor;
use Illuminate\Database\Seeder;

class ReceptoresDemoSeeder extends Seeder
{
    public function run(): void
    {
        $receptores = [
            [
                'tipo_documento' => '36',
                'num_documento' => '06141804941013',
                'nombre' => 'Distribuidora El Progreso, S.A. de C.V.',
                'nombre_comercial' => 'El Progreso',
                'correo' => 'compras@elprogreso.com.sv',
                'telefono' => '2222-3333',
                'departamento' => '06',
                'municipio' => '14',
                'direccion_complemento' => 'Col. Escalón, Calle Principal #123',
                'activo' => true,
                'nrc' => '1234567',
                'desc_actividad' => 'Venta al por menor',
                'distrito' => '14',
            ],
            [
                'tipo_documento' => '13',
                'num_documento' => '01234567-8',
                'nombre' => 'María Fernanda López Hernández',
                'nombre_comercial' => null,
                'correo' => 'mafer.lh@gmail.com',
                'telefono' => '7777-8888',
                'departamento' => '05',
                'municipio' => '18',
                'direccion_complemento' => 'Res. Los Álamos, Pol. C, Casa 45',
                'activo' => true,
                'nrc' => '1234567',
                'desc_actividad' => 'Venta al por menor',
                'distrito' => '14',
            ],
            [
                'tipo_documento' => '37',
                'num_documento' => 'CF',
                'nombre' => 'Consumidor Final',
                'nombre_comercial' => null,
                'correo' => null,
                'telefono' => null,
                'departamento' => null,
                'municipio' => null,
                'direccion_complemento' => null,
                'activo' => true,
                'nrc' => '1234567',
                'desc_actividad' => 'Venta al por menor',
                'distrito' => '14',
            ],
        ];

        foreach ($receptores as $datos) {
             Receptor::updateOrCreate(
                ['tipo_documento' => $datos['tipo_documento'], 'num_documento' => $datos['num_documento']],
                $datos
            );
        }
    }
}