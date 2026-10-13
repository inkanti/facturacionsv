<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\Domain as DomainContract;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * Dominio/subdominio por el que se accede a un tenant.
 * Ej.: demo.facturadorsv.test -> tenant "demo".
 */
class Domain extends BaseDomain implements DomainContract
{
    // Intencionalmente vacío: usamos el comportamiento base del paquete.
    // Aquí podríamos agregar validaciones propias en el futuro.
}