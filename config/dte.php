<?php

return [
    // simulado | mh — ver binding en AppServiceProvider
    'proveedor' => env('DTE_PROVIDER', 'simulado'),
    'simular_contingencia' => env('DTE_SIMULAR_CONTINGENCIA', false),
    // Retención legal: 10 años (convención del proyecto)
    'retencion_anios' => 10,
];