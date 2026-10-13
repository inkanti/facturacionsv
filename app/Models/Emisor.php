<?php

namespace App\Models;

use App\Enums\AmbienteMh;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Emisor (contribuyente) del tenant. Vive en la BD del tenant.
 *
 * OJO: $table es OBLIGATORIO. El pluralizador de Laravel es inglés
 * y convertiría "Emisor" en "emisors"; la tabla real es "emisores".
 */
class Emisor extends Model
{
    protected $table = 'emisores';

    protected $fillable = [
        'nit',
        'nrc',
        'nombre_razon_social',
        'nombre_comercial',
        'actividad_economica',
        'actividad_economica_desc',
        'tipo_establecimiento',
        'codigo_establecimiento',
        'direccion',
        'telefono',
        'correo',
        'ambiente',
        'estado',
    ];

    protected $casts = [
        'ambiente' => AmbienteMh::class,
    ];

    /** Certificados .p12 registrados para este emisor. */
    public function certificados(): HasMany
    {
        return $this->hasMany(Certificado::class);
    }

    /** Certificado activo actual (el que se usa para firmar). */
    public function certificadoActivo(): HasOne
    {
        return $this->hasOne(Certificado::class)->where('activo', true);
    }

    /** Credenciales de autenticación ante el MH. */
    public function credencialMh(): HasOne
    {
        return $this->hasOne(CredencialMh::class);
    }
}