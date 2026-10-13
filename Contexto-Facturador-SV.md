# FACTURADOR SV — Contexto del proyecto

> Documento vivo: resume lo construido, las decisiones tomadas y los aprendizajes.
> Última actualización: cierre de Módulos 0 y 1.

---

## 1. Qué es el proyecto

SaaS **multi-tenant** de facturación electrónica para El Salvador, dirigido a pequeños emprendedores que se están formalizando ante el Ministerio de Hacienda (MH).

- Uno o varios negocios se conectan de manera simultánea, cada uno con su propia base de datos.
- La integración con el MH **no se toca directamente**: todo pasa por la interfaz `App\Contracts\ProveedorDte` con dos implementaciones (`ProveedorDteSimulado` para desarrollo, `ProveedorDteMH` stub para producción).
- Aún no hay acceso al ambiente de pruebas del MH: todo lo que lo toque funciona con el proveedor simulado.

## 2. Estado actual — Módulos 0 y 1 cerrados y verificados

### Módulo 0: Fundaciones multi-tenant

- BD central `facturador_central` (tablas `tenants`, `domains` del paquete + columna personalizada `name`).
- BD por negocio con prefijo `tenant_` (`tenant_demo`), creada y migrada **automáticamente** al crear el tenant (hook `TenantCreated`: `CreateDatabase` + `MigrateDatabase`, sincrónicos).
- Resolución de tenant por dominio: `demo.facturadorsv.test` → tenant `demo`. Dominios centrales: `facturadorsv.test`, `localhost`, `127.0.0.1`.
- Cambio de conexión transparente al contexto del tenant (verificado: `config('database.default')` = `tenant`).
- Disco aislado por tenant (`storage/tenant{id}/...`): base para que el certificado `.p12` de cada negocio esté separado de los demás.
- Hosts locales: `127.0.0.1 facturadorsv.test demo.facturadorsv.test`.

### Módulo 1: Configuración del emisor

- Tablas de tenant: `emisores`, `certificados`, `credenciales_mh` (+ `users` del Módulo 0).
- Emisor demo sembrado: NIT `06141804941012`, ambiente `00` (pruebas).
- Certificado `.p12` cargado en **disco** (nunca en BD); en BD solo la ruta relativa y la contraseña **cifrada** (cast `encrypted`, verificado: legible solo a través del modelo).
- Credenciales del MH (NIT + contraseña) cifradas con cast `encrypted`.
- Servicio `App\Services\Emisor\GestorCertificado` garantiza un solo certificado activo por emisor y elimina el archivo al borrar el registro.
- Catálogos del MH (actividad económica, tipo de establecimiento) guardados como códigos; la validación contra el catálogo oficial se agregará cuando llegue la documentación del MH.

## 3. Stack y versiones confirmadas

| Pieza | Versión / decisión |
|---|---|
| Framework | Laravel 11+, PHP 8.3 |
| Paquete tenancy | `stancl/tenancy` **3.10.x** (estructura v3) |
| Estructura config v3 | Claves de primer nivel: `tenant_model`, `domain_model`, `id_generator`, `central_domains` |
| Eventos del tenancy | En `App\Providers\TenancyServiceProvider` (NO en `config/tenancy.php`): `TenantCreated → CreateDatabase + MigrateDatabase`, `TenancyInitialized → BootstrapTenancy`, `TenancyEnded → RevertToCentralContext`, `TenantDeleted → DeleteDatabase` |
| Modelo Tenant | Implementa `Stancl\Tenancy\Contracts\TenantWithDatabase` + traits `HasDatabase` + `HasDomains`; `getCustomColumns()` incluye `id`, `name`, `data`, timestamps |
| Managers de BD | `Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager` (sin `Database\` en el namespace) |
| Prefijo BD | `'tenant_'` — en v3.10 el nombre se compone prefijo + id + sufijo (concatenación directa) |
| Generador de IDs | `Stancl\Tenancy\UUIDGenerator` (los seeders siempre pasan id explícito) |
| `.env` (dev) | `CACHE_STORE=array`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync` → Redis en Módulo 3 |
| UI | Blade provisional; decisión Livewire 3 vs Inertia+Vue pendiente para el módulo de pantallas |

## 4. Convenciones del proyecto (reglas de arquitectura + aprendizadas)

1. **Todo modelo declara `protected $table` explícitamente.** El pluralizador de Laravel es inglés (`Emisor` → `emisors`); las tablas usan plural español (`emisores`). Este detalle costó varias horas de depuración.
2. Migraciones de tenant en `database/migrations/tenant/`; corren solas al crear el tenant.
3. Columnas monetarias: `decimal(12,2)`, jamás float.
4. Índices en `estado` y campos de búsqueda en toda tabla de tenant.
5. DTE nunca solo en BD: también en disco (retención 10 años).
6. Receptor guardado como snapshot dentro del documento (`receptor_nit`, `receptor_nombre`).
7. `numero_control` y `codigo_generacion` (UUID v4) se asignan **al emitir**, no al crear el borrador.
8. Estados del documento: `borrador → firmado → transmitido → validado / rechazado / contingencia`.
9. Certificado `.p12` en disco por tenant, nunca en BD; `.p12` fuera de git.
10. Comentarios de código en español; código entregado archivo por archivo con pasos de verificación (`artisan`/`tinker`).
11. Seeders idempotentes (`firstOrCreate`).
12. Redis solo para colas y cache; nunca como almacenamiento de datos.

## 5. Archivos del proyecto

### Modificados
- `config/tenancy.php` — config v3.10 con valores del proyecto
- `bootstrap/providers.php` — registro de `TenancyServiceProvider`
- `routes/tenant.php` — rutas de tenant (middleware `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains`)
- `routes/web.php` — landing central
- `.env` — conexión, cache/sesiones/colas en modo dev
- `.gitignore` — `storage/app/certificados_dev/`

### Creados
- `app/Providers/TenancyServiceProvider.php`
- `app/Models/Tenant.php`, `app/Models/Domain.php`
- `app/Models/Emisor.php`, `app/Models/Certificado.php`, `app/Models/CredencialMh.php`
- `app/Enums/AmbienteMh.php` (`00` pruebas / `01` producción)
- `app/Services/Emisor/GestorCertificado.php`
- `database/migrations/2026_10_12_000001_add_name_to_tenants_table.php` (central)
- `database/migrations/tenant/2026_10_12_100000_create_users_table.php`
- `database/migrations/tenant/2026_10_12_100001_create_emisores_table.php`
- `database/migrations/tenant/2026_10_12_100002_create_certificados_table.php`
- `database/migrations/tenant/2026_10_12_100003_create_credenciales_mh_table.php`
- `database/seeders/TenantDemoSeeder.php` (central, crea tenant demo)
- `database/seeders/TenantDemoDataSeeder.php` (central, siembra en cada tenant)
- `database/seeders/Tenant/EmisorDemoSeeder.php` (sembrado del emisor)
- `resources/views/tenant/welcome.blade.php` (vista provisional de verificación)

## 6. Riesgos técnicos ya resueltos (no repetir)

- **Config cacheada vieja:** si algo "no se aplica", `php artisan config:clear`.
- **Limpieza de tenants huérfanos:** siempre por SQL directo (evita disparar jobs con BD inexistente).
- **Namespaces del paquete:** verificar contra `vendor/stancl/tenancy` antes de asumir; la v3.10 es híbrida entre estructuras (managers en `TenantDatabaseManagers`, contrato en `Contracts\TenantWithDatabase`, trait en `Database\Concerns\HasDatabase`).
- **Cache por tenant:** v3 usa *tags*; drivers `file`/`database` no los soportan → `CACHE_STORE=array` en dev.
- **Registro de migraciones vs. tablas reales:** si una tabla existe pero su migración no está en `tenant_xxx.migrations`, insertar el registro manualmente (no re-correr la migración).

## 7. Roadmap

| Módulo | Estado | Contenido |
|---|---|---|
| 0 — Fundaciones multi-tenant | ✅ Cerrado | BD central + BD por tenant, migraciones automáticas, resolución por dominio |
| 1 — Configuración del emisor | ✅ Cerrado | Emisor, certificado `.p12` en disco, credenciales MH cifradas |
| 2 — Catálogos | ⏳ Siguiente | Receptores (clientes) y productos/servicios (IVA gravado/exento), seeders demo |
| 3 — Emisión de DTE | Pendiente | Borrador → cola (`FirmarTransmitirDocumento`) → firmado → transmitido → validado/rechazado; contingencia con `ReintentarTransmision`; Redis para colas/cache |
| 4 — Consulta y descarga | Pendiente | Listados, JSON/PDF del DTE en disco (retención 10 años) |
| 5 — Suscripciones y admin | Pendiente | Planes, subscriptions, admin_users en `facturador_central` |
| 6 — ProveedorDteMH real | Pendiente | JWT del MH + firma JWS (ES256), requiere ambiente de pruebas del MH |

## 8. Pendiente de conseguir (del MH / del negocio)

1. **Guía de llenado del DTE en JSON** (Factura Electrónica, v1/v2 del MH) — esquema exacto de campos. (Módulos 3 y 6)
2. **Manual de autenticación del MH** — obtención del token JWT con NIT + contraseña (~24h de vigencia, se cacheará en Redis), endpoints de pruebas/producción.
3. **Catálogos JSON del MH** — tipos de documento, tipos de establecimiento, condiciones de operación, actividades económicas.
4. **Credenciales del ambiente de pruebas del MH** (cuando se otorguen).
5. **Decisión de UI:** Livewire 3 vs Inertia+Vue (para el módulo de pantallas).

## 9. Comandos de verificación rápida

```bash
# Crear/recrar tenant demo (crea tenant_demo + migra)
php artisan db:seed --class=TenantDemoSeeder

# Verificar contexto de tenant
php artisan tinker --execute="tenancy()->initialize(App\Models\Tenant::find('demo')); var_dump(config('database.default'));"
# Esperado: string(6) "tenant"

# Sembrar datos de demo en un tenant
php artisan tenants:seed --class="Database\Seeders\Tenant\EmisorDemoSeeder" --tenants=demo

# Migrar tenants
php artisan tenants:migrate

# Verificación SQL
mysql -u root -e "SHOW TABLES IN tenant_demo;"
mysql -u root -e "SELECT nit, ambiente FROM tenant_demo.emisores; SELECT activo, nombre_archivo FROM tenant_demo.certificados; SELECT nit FROM tenant_demo.credenciales_mh;"
```

## 10. Contexto del MH (El Salvador) — recordatorio

- DTE en formato JSON, firmado con certificado `.p12` (ES256 → Compact JWS).
- Autenticación: JWT del MH con NIT + contraseña (válido ~24h, cachearlo).
- Ambiente pruebas = `00`, producción = `01`.
- Tipos de documento: FE=01, CCF=03, NC=04, ND=05, FEXE=11, NCE=13, NDE=14.
- IVA estándar: 13% sobre ventas gravadas; productos pueden ser exentos.
