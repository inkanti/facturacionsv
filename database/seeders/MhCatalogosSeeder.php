<?php

namespace Database\Seeders;

use App\Models\CatalogoMh;
use Illuminate\Database\Seeder;

/**
 * Siembra los catálogos del MH en la BD CENTRAL desde
 * resources/mh-catalogos/catalogos.json.
 * Cuando el MH publique nueva versión: reemplazar el JSON y re-correr.
 */
class MhCatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = resource_path('mh-catalogos/catalogos.json');
        abort_unless(file_exists($ruta), 404, "No existe {$ruta}");

        $json = json_decode(file_get_contents($ruta), true, 512, JSON_THROW_ON_ERROR);

        foreach ($json['catalogos'] as $catalogo) {
            foreach ($catalogo['items'] as $item) {
                CatalogoMh::updateOrCreate(
                    [
                        'tipo'   => $catalogo['tipo'],
                        'padre'  => $item['padre'],
                        'codigo' => (string) $item['codigo'],
                    ],
                    ['valor' => $item['valor']]
                );
            }
        }

        $this->command?->info('Catálogos MH sembrados: ' . CatalogoMh::count() . ' items.');
    }
}