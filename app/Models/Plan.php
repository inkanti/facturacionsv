<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Plan de suscripción (BD central).
 */
class Plan extends Model
{
    protected $connection; // se asigna en el constructor: siempre la BD central
    protected $table = 'planes';

    protected $fillable = [
        'nombre', 'slug', 'descripcion', 'precio_mensual',
        'limite_emisores', 'limite_usuarios', 'limite_documentos_mes',
        'caracteristicas', 'es_visible', 'estado', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'precio_mensual' => 'decimal:2',
            'caracteristicas' => 'array',
            'es_visible' => 'boolean',
        ];
    }

    public function __construct(array $attributes = [])
    {
        $this->connection = config('tenancy.database.central_connection', 'mysql');
        parent::__construct($attributes);
    }

    public function suscripciones()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function scopeVisibles($query)
    {
        return $query->where('es_visible', true)->where('estado', 'activo')->orderBy('orden');
    }
}