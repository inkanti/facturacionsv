<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Metadata de un certificado .p12. El ARCHIVO está en disco
 * (disco "local", aislado por tenant); aquí solo la ruta y
 * la contraseña cifrada.
 */
class Certificado extends Model
{
    protected $table = 'certificados';

    protected $fillable = [
        'emisor_id',
        'nombre_archivo',
        'ruta',
        'vigencia_inicio',
        'vigencia_fin',
        'activo',
        'password',
    ];

    protected $casts = [
        'vigencia_inicio' => 'date',
        'vigencia_fin'    => 'date',
        'activo'          => 'boolean',
        // Regla de arquitectura: contraseña SIEMPRE cifrada en BD.
        'password'        => 'encrypted',
    ];

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Emisor::class);
    }

    /**
     * Red de seguridad: al eliminar el registro, eliminar el archivo
     * del disco, para no dejar .p12 huérfanos.
     */
    protected static function booted(): void
    {
        static::deleting(function (Certificado $certificado) {
            Storage::disk('local')->delete($certificado->ruta);
        });
    }
}