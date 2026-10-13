<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Crea (o recrea) el tenant de demostración.
 * Al ejecutarse: crea la BD tenant_demo y corre las migraciones
 * de tenant automáticamente por el hook TenantCreated.
 */
class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate para que el seeder sea idempotente
        $tenant = Tenant::firstOrCreate(
            ['id' => 'demo'],
            ['name' => 'Negocio Demo']
        );

        $tenant->domains()->firstOrCreate(
            ['domain' => 'demo.facturadorsv.test']
        );

        $this->command->info("Tenant '{$tenant->id}' listo en tenant_{$tenant->id}");
    }
}