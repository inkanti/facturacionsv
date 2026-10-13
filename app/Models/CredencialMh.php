<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Credenciales NIT + contraseña para autenticarse ante el MH.
 * La contraseña se almacena cifrada (cast "encrypted").
 */
class CredencialMh extends Model
{
    // Nombre explícito: "credenciales_mh" no se puede deducir del nombre de la clase
    protected $table = 'credenciales_mh';

    protected $fillable = [
        'emisor_id',
        'nit',
        'password',
        'estado',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Emisor::class);
    }
}