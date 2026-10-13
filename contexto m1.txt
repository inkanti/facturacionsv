FACTURADOR SV — Contexto del proyecto a la fecha
Qué es
SaaS multi-tenant de facturación electrónica para El Salvador, dirigido a pequeños emprendedores que se formalizan ante el Ministerio de Hacienda (MH). Laravel 11+/PHP 8.3/MySQL 8, colas y cache en Redis (Módulo 3), integración al MH desacoplada detrás de App\Contracts\ProveedorDte.
Estado actual: Módulos 0 y 1 cerrados y verificados
Infraestructura multi-tenant funcionando:
BD central facturador_central (tablas tenants, domains del paquete + columna personalizada name).
BD por negocio con prefijo tenant_ (tenant_demo), creada y migrada automáticamente al crear el tenant.
Resolución de tenant por dominio: demo.facturadorsv.test → tenant demo; dominios centrales (facturadorsv.test, localhost) quedan fuera.
Cambio de conexión transparente al contexto del tenant (verificado: config('database.default') = tenant).
Disco aislado por tenant (storage/tenant{id}/...) — base para que el certificado .p12 de cada negocio esté separado.
Configuración del emisor sembrada en tenant_demo:
Tablas emisores, certificados, credenciales_mh (además de users).
Emisor demo (NIT 06141804941012, ambiente 00), certificado .p12 cargado en disco y contraseñas cifradas con cast encrypted (verificado: en BD se ven cifradas, el modelo las descifra).
Stack y versiones confirmadas
Pieza 	Versión / decisión 
Paquete tenancy	stancl/tenancy 3.10.x (estructura v3: eventos en App\Providers\TenancyServiceProvider, NO en config)
Modelo Tenant	Implementa Contracts\TenantWithDatabase + traits HasDatabase + HasDomains
Managers BD	Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager (sin Database\)
Prefijo BD	'tenant_' (v3.10 concatena prefijo+id directo)
.env dev	CACHE_STORE=array (v3 etiqueta cache por tenant con tags; file/database no soportan tags), SESSION_DRIVER=file, QUEUE_CONNECTION=sync → Redis en Módulo 3
UI	Blade provisional; decisión Livewire vs Inertia pendiente para el módulo de pantallas
Convenciones establecidas (aprendidas en el camino)
Todo modelo declara protected $table explícitamente — el pluralizador de Laravel es inglés: Emisor → emisors (la tabla real es emisores). Casi nos cuesta el proyecto este detalle.
Migraciones de tenant en database/migrations/tenant/, corren solas vía hook TenantCreated (CreateDatabase + MigrateDatabase, sincrónicos).
Monetarias: decimal(12,2), jamás float (a aplicar en Módulo 2).
Índices en estado y campos de búsqueda en toda tabla de tenant.
Certificados en disco (nunca BD), contraseñas cifradas, .p12 fuera de git (.gitignore: storage/app/certificados_dev/).
Comentarios en el código en español, código entregado archivo por archivo con pasos de verificación artisan/tinker.
Seeders idempotentes (firstOrCreate).
Archivos creados/modificados hasta ahora
Modifica: config/tenancy.php, bootstrap/providers.php, routes/tenant.php, .env, .gitignore
Crea: app/Providers/TenancyServiceProvider.php, app/Models/{Tenant,Domain,Emisor,Certificado,CredencialMh}.php, app/Enums/AmbienteMh.php, app/Services/Emisor/GestorCertificado.php, migración central add_name_to_tenants_table, 4 migraciones tenant (users, emisores, certificados, credenciales_mh), database/seeders/{TenantDemoSeeder,TenantDemoDataSeeder}.php, database/seeders/Tenant/EmisorDemoSeeder.php, resources/views/tenant/welcome.blade.php
Riesgos técnicos ya resueltos (para no repetirlos)
Config cacheada vieja → si algo "no se aplica": php artisan config:clear.
Limpiar tenants huérfanos siempre por SQL directo (evita disparar jobs con BD inexistente).
Verificar nombres de clases contra vendor/stancl/tenancy antes de asumir namespaces (la v3.10 es híbrida entre estructuras).
Pendiente de tu parte
Documentación del MH (la necesitaré en Módulos 1-fin y 6): guía de llenado del DTE en JSON (FE v1/v2), manual de autenticación JWT (NIT + contraseña, ~24h), catálogos JSON, endpoints de pruebas/producción, y credenciales del ambiente de pruebas cuando las tengas.
Decisión pendiente (sin prisa): Livewire 3 vs Inertia+Vue para las pantallas.
Siguiente paso
Módulo 2 — Catálogos: tablas receptores (clientes; NIT/DUI, nombre, dirección, correo — el documento guardará snapshot del receptor según tu regla 6) y productos (código, descripción, precio decimal(12,2), afectación de IVA: gravado 13% / exento / no sujeto), con seeders demo y verificación.