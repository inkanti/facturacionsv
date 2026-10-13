<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Modelo central de tenant (vive en facturador_central).
 *
 * Un tenant = un negocio. Cada uno tiene su propia base de datos
 * tenant_{id} con todas sus tablas operativas.
 *
 * TenantWithDatabase (contrato + trait HasDatabase) es obligatorio en
 * esta versión del paquete: sin él los jobs CreateDatabase/
 * MigrateDatabase y el cambio de conexión no aceptan el modelo.
 */
class Tenant extends BaseTenant implements TenantContract, TenantWithDatabase
{
    use HasDomains, HasDatabase;

    /**
     * Columnas "reales" de la tabla tenants.
     *
     * Todo atributo que NO esté en esta lista se serializa dentro de
     * la columna JSON `data` del paquete. Por eso 'name' (nuestra
     * columna personalizada) y 'data' deben listarse explícitamente.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'data',
            'created_at',
            'updated_at',
        ];
    }
}