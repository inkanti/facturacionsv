<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogos oficiales del MH. BD CENTRAL (convención 17): la conexión se
 * asigna en el constructor para sobrevivir al cambio de conexión del tenancy.
 */
class CatalogoMh extends Model
{
    protected $table = 'mh_catalogos';

    protected $fillable = ['tipo', 'codigo', 'valor', 'padre'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('tenancy.database.central_connection', 'mysql');
    }

    /** Items de un catálogo, opcionalmente por padre (departamento). */
    public function scopeDeTipo($query, string $tipo, ?string $padre = null)
    {
        return $query->where('tipo', $tipo)
            ->when($padre !== null, fn ($q) => $q->where('padre', $padre))
            ->orderBy('codigo')
            ->pluck('valor', 'codigo');
    }
}