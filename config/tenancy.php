<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| FACTURADOR SV — Configuración de tenancy
|--------------------------------------------------------------------------
| Estructura exacta de stancl/tenancy v3.10.x (verificada contra el
| código fuente de esa versión).
|
| NOTA importante: en v3 los EVENTOS del tenancy (crear BD al crear
| tenant, ejecutar bootstrappers al inicializar, etc.) NO se configuran
| aquí: viven en App\Providers\TenancyServiceProvider.
*/
return [
    'tenant_model' => \App\Models\Tenant::class,
    'domain_model' => \App\Models\Domain::class,

    /*
    | Generador de IDs del paquete. En nuestros seeders SIEMPRE pasamos
    | el id explícito ('demo', 'negocio1'...), de modo que la BD quede
    | tenant_demo, tenant_negocio1... El generador solo actuaría si se
    | creara un tenant sin id (generaría un UUID).
    */
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    /*
    | Dominios centrales: todo lo demás se resuelve como tenant.
    */
    'central_domains' => [
        'facturadorsv.test',
        'localhost',
        '127.0.0.1',
    ],

    /*
    | Bootstrappers: se ejecutan al inicializar el tenancy (mediante el
    | listener BootstrapTenancy del provider). Database cambia la conexión
    | a la BD del tenant; Filesystem aísla el disco (certificado .p12).
    */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class, // módulo 3 (colas/cache en Redis)
    ],

    /*
    | Base de datos. OJO: en v3.10 el nombre se compone como
    | prefijo + id + sufijo (concatenación directa), por eso el
    | prefijo lleva guion bajo: tenant_demo, tenant_negocio1...
    */
    'database' => [
        'central_connection'         => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,
        'prefix'                     => 'tenant_',
        'suffix'                     => '',
        'managers' => [
            'sqlite'  => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql'   => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql'   => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    /*
    | Cache por tenant (v3 lo implementa con TAGS). El driver de cache
    | DEBE soportar tags: 'array', 'redis' o 'memcached'. 'file' y
    | 'database' NO soportan tags -> usar CACHE_STORE=array en .env.
    */
    'cache' => [
        'tag_base' => 'tenant',
    ],

    /*
    | Disco por tenant: storage/tenant{id}/... Es lo que mantiene el
    | certificado .p12 de cada negocio aislado de los demás.
    */
    'filesystem' => [
        'suffix_base'         => 'tenant',
        'disks'               => ['local', 'public'],
        'root_override'       => [
            'local'  => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    'redis' => [
        'prefix_base'          => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [],

    'routes' => true,

    /*
    | Migraciones de tenant: las de database/migrations/tenant/ corren
    | automáticamente al crear el tenant (pipeline del provider).
    */
    'migration_parameters' => [
        '--force'    => true,
        '--path'     => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],
];