<?php

namespace Database\Seeders\Tenant;

use App\Enums\AmbienteMh;
use App\Models\Emisor;
use App\Services\Emisor\GestorCertificado;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Seeder DE TENANT: datos de demostración del emisor.
 *
 * Se ejecuta DENTRO del contexto de un tenant (php artisan tenants:seed),
 * por lo que no necesita tenancy()->initialize().
 *
 * Es idempotente: usa firstOrCreate, se puede correr cuantas veces
 * quieras sin duplicar registros.
 *
 * IMPORTANTE: NIT, correo y contraseñas son valores de DESARROLLO.
 * Reemplázalos con datos reales antes de usar otro ambiente que
 * no sea pruebas ('00').
 */
class EmisorDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------
        // 1. Emisor
        // ------------------------------------------------------------------
        $emisor = Emisor::firstOrCreate(
            ['nit' => '06141804941012'], // busca por NIT
            [
                'nrc'                    => '1234567',
                'nombre_razon_social'    => 'Negocio Demo, S.A. de C.V.',
                'nombre_comercial'       => 'Negocio Demo',
                'actividad_economica'    => '10005', // código MH pendiente de catálogo
                'actividad_economica_desc' => null,
                'tipo_establecimiento'   => '02',    // código MH pendiente de catálogo
                'codigo_establecimiento' => '0001',
                'direccion'              => 'Col. Escalón, Calle Foo #123, San Salvador',
                'telefono'               => '2222-2222',
                'correo'                 => 'facturacion@negociodemo.test',
                'ambiente'               => AmbienteMh::Pruebas, // '00'
                'estado'                 => 'activo',
            ]
        );

        // ------------------------------------------------------------------
        // 2. Credenciales del MH (contraseña cifrada vía cast "encrypted")
        // ------------------------------------------------------------------
        $emisor->credencialMh()->firstOrCreate(
            ['nit' => '06141804941012'],
            ['password' => 'clave-del-portal-MH'], // TODO: valor de desarrollo
        );

        // ------------------------------------------------------------------
        // 3. Certificado .p12 de desarrollo
        //    Solo si existe el archivo y el emisor aún no tiene uno activo.
        //    Coloca el .p12 en: storage/app/certificados_dev/certificado_demo.p12
        // ------------------------------------------------------------------
        $rutaDev = storage_path('app/certificados_dev/certificado_demo.p12');

        if (is_file($rutaDev) && $emisor->certificadoActivo()->doesntExist()) {
            $archivo = new UploadedFile($rutaDev, 'certificado_demo.p12', test: true);

            $certificado = app(GestorCertificado::class)
                ->guardar($emisor, $archivo, env('CERT_DEMO_PASSWORD', 'Demo1234'));

            $this->command?->info("Certificado cargado: {$certificado->nombre_archivo}");
        }

        $this->command?->info("Emisor listo: {$emisor->nombre_razon_social} (NIT {$emisor->nit})");
    }
}