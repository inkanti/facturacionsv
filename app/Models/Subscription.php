<?php

namespace App\Models;

use App\Enums\EstadoSuscripcion;
use Illuminate\Database\Eloquent\Model;

/**
 * Suscripción de un tenant (BD central).
 */
class Subscription extends Model
{
    protected $connection; // se asigna en el constructor: siempre la BD central
    protected $table = 'suscripciones';

    protected $fillable = [
        'tenant_id', 'plan_id', 'estado', 'inicia_en', 'vence_en',
        'trial_hasta', 'cancelado_en', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoSuscripcion::class,
            'inicia_en' => 'date',
            'vence_en' => 'date',
            'trial_hasta' => 'date',
            'cancelado_en' => 'datetime',
        ];
    }

    public function __construct(array $attributes = [])
    {
        $this->connection = config('tenancy.database.central_connection', 'mysql');
        parent::__construct($attributes);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /** Vigencia en caliente: el estado importa, pero también la fecha. */
    public function estaVigente(): bool
    {
        if (! $this->estado?->permiteAcceso()) {
            return false;
        }

        $hoy = now()->startOfDay();

        return match ($this->estado) {
            EstadoSuscripcion::Activa => $this->vence_en === null
                || $this->vence_en->greaterThanOrEqualTo($hoy),
            EstadoSuscripcion::Trial => $this->trial_hasta !== null
                && $this->trial_hasta->greaterThanOrEqualTo($hoy),
            default => false,
        };
    }
}