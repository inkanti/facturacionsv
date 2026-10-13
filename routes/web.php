<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas centrales
|--------------------------------------------------------------------------
| Solo landing y, en el futuro, registro de negocios y panel admin.
| Los dominios centrales se definen en config/tenancy.php.
*/
Route::get('/', function () {
    return 'FACTURADOR SV — portal central (módulo 5: registro y suscripciones)';
});