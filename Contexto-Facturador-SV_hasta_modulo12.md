# Facturador SV — Contexto del proyecto (actualizado al Módulo 11, inicio Módulo 12)

Documento de continuación. Léelo completo al inicio del nuevo chat y retoma desde
la sección 11 (Módulo 12 en curso).

---

## 1. RESUMEN DEL PROYECTO

**Facturador SV**: SaaS multi-tenant de facturación electrónica (DTE) para El
Salvador, dirigido a pequeños negocios. Emite DTE ante el MH (Ministerio de
Hacienda): FE (01, consumidor final) y CCF (03, crédito fiscal). Arquitectura
administrada por asistente: el usuario pega/verifica archivos y aplica cambios
paso a paso.

**Stack**: Laravel 12, PHP 8.2, Livewire 4.4.7 (full-page components con
`->layout('tenant.layout')`), stancl/tenancy 3.10, MySQL (central
`facturador_central` + tenants `tenant_xxx`), Redis (Memurai en Windows, puerto
6379) para colas (queue `dte`) y caché, predis.

**Dominios dev**: `facturadorsv.test` (central/admin) y
`demo.facturadorsv.test` (tenant demo). Entrar a `/admin/login` con
admin@facturadorsv.test (Módulo 5).

**Proveedor MH**: simulado, pero con validación real de JSON Schema
(opis/json-schema, draft-07). `json_decode` SIN assoc para el validador.
FE (01) usa schema v2; CCF/NC/ND usan v4.

---

## 2. ESTADO DE MÓDULOS

CERRADOS: 0 (setup), 1 (tenancy), 2 (emisores), 3 (numeración), 4 (JSON DTE),
5 (suscripciones + admin central), 6 (firma), 7 (transmisión), 8 (consulta y
pantallas), 9 (PDF), 10 (email automático), 11 (CRUD receptores y productos).

### Módulo 10 — Email automático (CERRADO)
Al validarse un documento se envía por correo al cliente el PDF + JSON adjuntos
(Mailable `DocumentoValidado`, `Attachment::fromData`). Columnas en
`documentos`: `coro_enviado_en` (timestamp nullable) y `coro_intentos`
(smallint default 0) — en realidad: `correo_enviado_en`, `correo_intentos`.
Gmail SMTP requiere "contraseña de aplicación" (no la normal). MAIL_MAILER=log
solo escribe en storage/logs/laravel.log, NO envía.

### Módulo 11 — CRUD receptores y productos (CERRADO)
- **Catálogos MH en base de datos central** (no en archivos): tabla
  `facturador_central.mh_catalogos` (id, tipo(8), codigo(8), valor(255),
  padre(8) nullable, unique(tipo,padre,codigo)). 33 catálogos, 1,680 items,
  sembrados desde `resources/mh-catalogos/catalogos.json` (extraído del XLSX
  oficial) vía `MhCatalogosSeeder`. Modelo central `App\Models\CatalogoMh`
  con scope `deTipo($tipo, $padre = null)`.
- **Jerarquías MH resueltas**: CAT-012 = 15 departamentos; CAT-013 = 44 ZONAS
  con códigos repetidos por departamento (padre = departamento); CAT-008 = 262
  municipios reales (el código reinicia en '01' por departamento; padre =
  departamento). La clave única real es (tipo, padre, codigo).
- **Esquema `tenant_demo.receptores`** (verificado): id, tipo_documento
  varchar(2) default '36' (36=NIT, 13=DUI, 37=Consumidor final, 03=Pasaporte,
  02=Carnet residente), num_documento varchar(20), nrc varchar(8), nombre
  varchar(200), nombre_comercial, actividad_economica varchar(10),
  desc_actividad varchar(150), correo varchar(100), telefono varchar(25),
  departamento/municipio/distrito varchar(2), direccion_complemento
  varchar(200), activo tinyint(1) default 1, timestamps.
- **Esquema `tenant_demo.productos`** (verificado): codigo varchar(30) UNI,
  nombre varchar(150), descripcion varchar(500), tipo varchar(1) default '1'
  (1=Bienes, 2=Servicios CAT-011), unidad_medida tinyint(3) default 59,
  precio_unitario decimal(12,2), tipo_iva varchar(20) default 'gravado',
  porcentaje_iva decimal(4,2) default 13.00, activo tinyint(1).
- **Formulario de receptores**: si tipo_documento=37 (consumidor final), el
  formulario solo exige nombre (campos fiscales ocultos con
  `@unless($this->esConsumidorFinal)`); num_documento se guarda como 'CF';
  telefono default '00000000'; actividad_economica + desc se heredan del
  Emisor activo si vienen vacíos. Cascada departamento→municipio→distrito
  desde catálogos (departamentos CAT-012, municipios CAT-008, zonas CAT-013).
- **PANTALLA DE EMISIÓN FILTRA RECEPTORES** (`app/Livewire/Tenant/Documentos/
  Crear.php`): con CCF (03) solo lista receptores con NRC y actividad
  económica; con FE (01) lista todos. `updatedTipoDocumento()` limpia la
  selección si no califica; `emitir()` valida de nuevo. La vista
  `resources/views/livewire/tenant/documentos/crear.blade.php` usa
  `wire:model.live="tipo_documento"`. OJO: un registro CF con NRC falso lleno
  SÍ aparece en CCF — el filtro es por dato, no por tipo_documento.
- **REGLA MH APRENDIDA (FE schema v2)**: `receptor.codActividad` (min 5) y
  `receptor.telefono` (min 8) son OBLIGATORIOS SIEMPRE, incluso consumidor
  final. Por eso los defaults al guardar un CF.
- Pendiente menor (no bloqueante): confirmar que el generador de JSON usa
  `porcentaje_iva`/`tipo` de productos (probar con producto exento; si falla,
  pedir `app/Services/Dte/EmisorDocumentos.php` o GeneradorJsonDte).

---

## 3. MÓDULO 12 — USUARIOS Y ACCESO (EN CURSO, empezar aquí)

**Objetivo**:
1. Login del tenant (correo + contraseña) en el dominio del negocio.
2. Bloquear rutas del tenant (receptores, productos, documentos) con sesión.
3. Primer usuario: si el tenant no tiene usuarios, pantalla de setup inicial.
4. CRUD de usuarios del negocio desde el panel (crear/editar/desactivar).

**Ya verificado al cerrar el Módulo 11**:
- La tabla `users` EXISTE en `tenant_demo` con columnas estándar: id, name,
  email, email_verified_at, password, remember_token, created_at, updated_at.
  No hace falta migración.
- El admin central ya tiene login propio: guard `admin`, modelo
  `App\Models\AdminUser`, `app/Http/Controllers/Admin/Auth/LoginController.php`,
  ruta `admin.login` (facturadorsv.test/admin/login), dashboard, tenants CRUD,
  planes. `bootstrap/app.php` ya tiene `redirectGuestsTo(fn () =>
  route('admin.login'))` — HAY QUE VOLVERLO CONDICIONAL para no romper el
  tenant (si la request es del tenant, redirigir al login del tenant).

**PRIMER PASO en el nuevo chat**: pedir al usuario que adjunte (la subida falló
la última vez y hubo que pedirla de nuevo):
1. `app/Models/User.php`
2. `routes/tenant.php`
3. `bootstrap/app.php`
Con eso diseñar: LoginController (tenant), vista login, rutas guest en
tenant.php, middleware `auth` en rutas de negocio, redirectGuestsTo
condicional, componente Livewire CRUD usuarios (mismo patrón que receptores).

**Decisión tomada por el usuario**: la creación de empresas (tenants) será
SOLO desde el admin central (eso es el Módulo 13, después del 12).

**Roadmap acordado después del 12**: Módulo 13 (creación de empresas desde
admin central, con creación del primer usuario del tenant), luego Cuentas por
cobrar, Anexo de IVA, Notas sobre FE (NC/ND ya existen parcialmente del
Módulo 8).

---

## 4. MODELOS Y SERVICIOS CLAVE EXISTENTES

- `App\Models\Receptor`, `App\Models\Producto` (tenant). `$fillable` debe
  incluir TODAS las columnas masivamente asignables (13 campos + activo en
  Receptor; 9 en Producto).
- `App\Models\Emisor` (tenant): scope `activo()`, campos nrc,
  codigo_establecimiento, actividad_economica, actividad_economica_desc.
- `App\Models\Tenant` (central), trait HasDatabase; evento Tenant::created
  dispara trial (Módulo 5).
- `App\Models\AdminUser` (central, guard admin), `App\Models\Plan`,
  `App\Models\Subscription`, enum `EstadoSuscripcion`.
- `App\Services\Dte\EmisorDocumentos`: crearBorrador($receptor, $items),
  emitir($documento). NumeroALetras: método REAL es `aLetras(float): string`
  (NO enLetras). GeneradorPdfDte genera el PDF. Jobs: FirmarTransmitirDocumento
  (cola `dte`), EnviarDocumentoCliente.
- Middleware `suscripcion.activa` (EnsureSubscriptionActiva) protege rutas de
  negocio del tenant; ruta suscripcion.estado queda fuera del bloqueo.
- Livewire: `Livewire::setUpdateRoute` configurado para tenancy (ver
  TenancyServiceProvider). Componentes full-page con `->layout('tenant.layout')`.

---

## 5. CONVENCIONES DEL PROYECTO (respetar)

1. Migrations con nombre de tabla explícito (`$table`), imports concretos de
   Schema, columnas money como `decimal(12,2)`.
2. Toda columna masivamente asignable va en `$fillable` del modelo.
3. Comentarios en español en el código.
4. numero_control / codigo_generacion se asignan al emitir, no al crear el
   borrador.
5. Jobs reciben tenantId y hacen `tenancy()->initialize()` / `tenancy()->end()`
   en try/finally. La tabla `failed_jobs` queda en la base CENTRAL (porque
   tenancy()->end() corre en el finally antes de que Laravel registre el
   fallo).
6. (Conv. 23) NUNCA redeclarar `public string $queue` en un Job: conflicta con
   el trait Queueable ("Class was composed"). Usar `$this->onQueue('dte')` en
   el constructor.
7. (Conv. 24) PowerShell destruye `tinker --execute='...'` con comillas
   anidadas (PsySH recibe código truncado tipo `'echo` y falla en silencio o
   con "Unexpected end of input"). Patrón confiable:
   ```powershell
   @'
   file_put_contents('check.txt', ...codigo php...);
   '@ | php artisan tinker
   Get-Content check.txt
   ```
   Ojo: tinker corre SIN tenant (consulta facturador_central). Para tocar
   datos del tenant hay que inicializar:
   ```php
   $tenant = App\Models\Tenant::first();
   tenancy()->initialize($tenant);
   ...consultas...
   tenancy()->end();
   ```
8. Cache en contexto tenant: stancl/tenancy v3 etiqueta el caché por tenant;
   el driver DEBE soportar tags → `CACHE_STORE=redis` (file/array/database
   tiran "Este sistema de almacenamiento en caché no admite el etiquetado").
   Después de cambiar .env: `php artisan config:clear`.
9. `queue:work` cachea código: reiniciar el worker tras cambiar jobs
   (`php artisan queue:restart` o Ctrl+C y de nuevo). `queue:flush` vacía.
10. Blade compilado: `php artisan view:clear` si una vista "no se aplica".
11. Migraciones que agregan columnas: guardas `Schema::hasColumn()`.
12. QR (chillerlan/php-qrcode v5): no existen `QRCode::OUTPUT_*`; usar string
    'png'; `render()` ya devuelve data URI completo (no base64_encode otra vez).
13. MH: header User-Agent obligatorio; token Bearer de /seguridad/auth.

---

## 6. ERRORES YA RESUELTOS (NO REPETIR)

- "Ruta [login] no definida" en /admin/*: falta redirectGuestsTo en
  bootstrap/app.php apuntando a admin.login (Módulo 5). AL AGREGAR auth al
  tenant, convertir esto en condicional.
- Namespaces stancl v3.10 híbridos: managers en TenantDatabaseManagers,
  contrato TenantWithDatabase en Contracts, trait HasDatabase en
  Database\Concerns.
- Limpieza de tenants huérfanos: SIEMPRE por SQL directo (crear el tenant
  dispara jobs que crean la BD; borrar por modelo deja basura y errores).
- Si una tabla existe pero su migración no está en tenant_xxx.migrations,
  insertar el registro a mano en esa tabla.
- hosts de Windows se editan como administrador:
  `127.0.0.1 facturadorsv.test demo.facturadorsv.test`.
- MH rechazó FE a consumidor final por `codActividad` (min 5) y `telefono`
  (min 8) vacíos → defaults al guardar CF (ver sección 2).
- Receptor CF aparecía en el select de CCF: tenía un NRC falso ("1234567")
  guardado antes del ajuste del formulario; el filtro es por NRC+actividad,
  no por tipo. Se limpió con update(['nrc' => null]).

---

## 7. FLUJO DE TRABAJO CON EL USUARIO (importante)

- El usuario trabaja en Windows/PowerShell, copia y pega archivos a mano.
- **Formato de entrega que exige**: MINIMALISTA. "No me des todo el módulo,
  solo los archivos que necesito corregir. Mejor dime: reemplaza el archivo
  xxxx/xxxxx/xxxx.php". NUNCA volcar un módulo entero; dar pasos numerados
  con: ruta exacta del archivo, qué reemplazar (o crear), y el snippet
  exacto. Si hay ambigüedad sobre nombres de propiedades, pedirle que adjunte
  el archivo ("si tienes dudas o necesitas corroborar algún archivo, pídeme
  que lo adjunte" — él lo hace).
- Cuando algo "no funciona", pedir verificaciones concretas numeradas
  (Select-String, tinker con file_put_contents) en vez de adivinar.
- Las subidas de archivos a veces tardan o fallan; si el archivo no aparece
  en la carpeta, pedir que lo re-adjunte.
