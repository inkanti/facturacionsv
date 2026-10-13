<?php

namespace App\Models;

use App\Enums\TipoIva;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tipo',
        'unidad_medida',
        'precio_unitario',
        'tipo_iva',
        'porcentaje_iva',
        'activo',
    ];

    protected $casts = [
        'tipo_iva' => TipoIva::class,   // string en BD <-> enum en código
        'precio_unitario' => 'decimal:2',
        'porcentaje_iva' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        return $query->where(function (Builder $q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('codigo', 'like', "%{$termino}%");
        });
    }

    public function esServicio(): bool
    {
        return $this->tipo === '2';
    }

    // Precio final con IVA (para mostrar en pantallas si se necesita)
    public function precioConIva(): float
    {
        $base = (float) $this->precio_unitario;
        return round($base * (1 + $this->tipo_iva->porcentaje() / 100), 2);
    }
}