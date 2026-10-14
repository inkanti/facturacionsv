<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

use App\Enums\EstadoSuscripcion;
use App\Models\Subscription;
use App\Models\Tenant;

/**
 * Provider de tenancy de FACTURADOR SV — formato stancl/tenancy v3.10.x.
 *
 * En v3 los eventos del tenancy se registran AQUÍ (no en config).
 * Este provider es OBLIGATORIO: sin él no se crean las BD de los
 * tenants ni se ejecutan los bootstrappers (cambio de conexión).
 */
class TenancyServiceProvider extends ServiceProvider
{
    public static string $controllerNamespace = '';

    /** Mapa de eventos del tenancy. */
    public function events(): array
    {
        return [
            // Al CREAR un tenant: crear su BD y correr las migraciones
            // de database/migrations/tenant/ automáticamente.
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    Jobs\MigrateDatabase::class,
                    // Jobs\SeedDatabase::class, // se activa cuando definamos seeders automáticos
                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // sincrónico: la BD existe de inmediato
            ],

            Events\SavingTenant::class   => [],
            Events\TenantSaved::class    => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class  => [],

            // Al ELIMINAR un tenant: borrar su BD (los dominios se
            // eliminan por cascade de la FK en la tabla domains).
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class  => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false),
            ],

            // CLAVE: al inicializar el tenancy se ejecutan los
            // bootstrappers (DatabaseTenancyBootstrapper = conexión
            // a tenant_{id}, FilesystemTenancyBootstrapper = disco...).
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],
            // Al terminar el contexto se revierte a la conexión central.
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],
        ];
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {

        $this->bootEvents();
        $this->mapRoutes();
        $this->makeTenancyMiddlewareHighestPriority();

        // Módulo 5: todo tenant nuevo inicia con prueba gratis de 14 días
        Tenant::created(function (Tenant $tenant) {
            Subscription::firstOrCreate(
                ['tenant_id' => $tenant->getTenantKey(), 'estado' => EstadoSuscripcion::Trial],
                ['trial_hasta' => now()->addDays(14)->toDateString()]
            );
        });

    }

    protected function bootEvents(): void
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    /** Carga routes/tenant.php. */
    protected function mapRoutes(): void
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority(): void
    {
        $tenancyMiddleware = [
            // Mayor prioridad que el middleware de inicialización
            Middleware\PreventAccessFromCentralDomains::class,

            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }
}