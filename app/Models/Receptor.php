<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Receptor extends Model
{
    // Tabla explícita: el pluralizador de Laravel daría "receptors"
    protected $table = 'receptores';

    protected $fillable = [
        'tipo_documento',
        'num_documento',
        'nombre',
        'nombre_comercial',
        'actividad_economica',
        'correo',
        'telefono',
        'departamento',
        'municipio',
        'direccion_complemento',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Solo catálogo activo (para selects al emitir)
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    // Búsqueda por nombre o número de documento
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        return $query->where(function (Builder $q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('num_documento', 'like', "%{$termino}%");
        });
    }

    // Acceso rápido para la vista previa / emisión
    public function esConsumidorFinal(): bool
    {
        return $this->num_documento === null;
    }
}