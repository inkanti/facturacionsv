<?php

namespace App\Models;

use App\Enums\TipoIva;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoDetalle extends Model
{
    protected $table = 'documento_detalles';

    protected $fillable = [
        'documento_id', 'producto_id', 'codigo', 'nombre',
        'cantidad', 'precio_unitario', 'tipo_iva', 'iva', 'subtotal',
    ];

    protected $casts = [
        'tipo_iva' => TipoIva::class,
        'cantidad' => 'decimal:3',
        'precio_unitario' => 'decimal:2',
        'iva' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}