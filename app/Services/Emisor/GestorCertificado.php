<?php

namespace App\Services\Emisor;

use App\Models\Certificado;
use App\Models\Emisor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Gestiona el almacenamiento del certificado .p12 del emisor.
 *
 * Reglas que cumple:
 *  - El archivo va a DISCO (disco "local"), nunca a BD.
 *  - El disco ya está aislado por tenant (FilesystemTenancyBootstrapper),
 *    por lo que cada negocio solo ve su propio storage.
 *  - Solo un certificado activo por emisor.
 *  - La contraseña se cifra vía cast "encrypted" del modelo.
 */
class GestorCertificado
{
    /**
     * Guarda un nuevo .p12 para el emisor y lo deja como activo.
     *
     * @param  Emisor        $emisor
     * @param  UploadedFile  $archivo   Archivo .p12 subido
     * @param  string        $password  Contraseña del .p12
     */
    public function guardar(Emisor $emisor, UploadedFile $archivo, string $password): Certificado
    {
        // 1. Guardar el archivo en disco (ruta relativa, ej.:
        //    "certificados/abc123.p12"). El disco es por tenant.
        $ruta = $archivo->store('certificados', 'local');

        // 2. Desactivar certificados anteriores (transacción por si falla)
        $emisor->certificados()->where('activo', true)->update(['activo' => false]);

        // 3. Registrar en BD. La contraseña se cifra automáticamente
        //    al asignarse por el cast "encrypted".
        return $emisor->certificados()->create([
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta'           => $ruta,
            'password'       => $password,
            'activo'         => true,
        ]);
    }

    /** Elimina un certificado: registro de BD + archivo en disco. */
    public function eliminar(Certificado $certificado): void
    {
        // El archivo se borra en el hook "deleting" del modelo;
        // aquí solo eliminamos el registro.
        $certificado->delete();
    }
}