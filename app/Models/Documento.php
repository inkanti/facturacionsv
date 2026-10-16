<?php

namespace App\Models;

use App\Enums\EstadoDocumento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Documento extends Model
{
    protected $table = 'documentos';

    protected $fillable = [
        'tipo_documento', 'numero_control', 'codigo_generacion',
        'documento_padre_id',
        'receptor_id', 'receptor_tipo_documento', 'receptor_num_documento',
        'receptor_nombre', 'receptor_correo', 'receptor_snapshot',
        'estado', 'subtotal', 'iva', 'total',
        'json_dte', 'ruta_json', 'firma_jws', 'ruta_firma',
        'respuesta_mh', 'reintentos', 'fecha_emision',
        'correo_enviado_en', 'correo_intentos',
    ];

    protected $casts = [
        'estado' => EstadoDocumento::class,
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
        'receptor_snapshot' => 'array',
        'json_dte' => 'array',
        'respuesta_mh' => 'array',
        'fecha_emision' => 'datetime',
    ];

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(Receptor::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DocumentoDetalle::class);
    }

    /** Documento que esta nota (05/06) afecta. */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'documento_padre_id');
    }

    /** Notas (05/06) emitidas sobre este documento. */
    public function notas(): HasMany
    {
        return $this->hasMany(Documento::class, 'documento_padre_id');
    }

    /** borrador + número de control asignado = emitido, esperando la cola. */
    public function estaEnProceso(): bool
    {
        return $this->estado === EstadoDocumento::Borrador
            && $this->numero_control !== null;
    }

    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        return $query->where(function (Builder $q) use ($termino) {
            $q->where('numero_control', 'like', "%{$termino}%")
              ->orWhere('receptor_nombre', 'like', "%{$termino}%");
        });
    }
}