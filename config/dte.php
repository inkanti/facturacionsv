<?php

return [
    // simulado | mh — ver binding en AppServiceProvider
    'proveedor' => env('DTE_PROVIDER', 'simulado'),
    'simular_contingencia' => env('DTE_SIMULAR_CONTINGENCIA', false),
    // Retención legal: 10 años (convención del proyecto)
    'retencion_anios' => 10,
    /*
    |--------------------------------------------------------------------------
    | Envío automático al cliente (Módulo 10)
    |--------------------------------------------------------------------------
    | Al llegar un documento a "validado" se encola el envío del PDF + JSON
    | al correo del receptor (tomado del snapshot del documento).
    */
    'envio_cliente' => [
        'activo' => env('ENVIO_CLIENTE_ACTIVO', true),
    ],
];

    