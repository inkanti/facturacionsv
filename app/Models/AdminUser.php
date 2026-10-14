<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Administrador de la plataforma (BD central, guard "admin").
 */
class AdminUser extends Authenticatable
{
    use Notifiable;

    protected $connection; // se asigna en el constructor: siempre la BD central
    protected $table = 'admin_users';

    protected $fillable = ['nombre', 'email', 'password', 'estado', 'ultimo_acceso_en'];

    protected $hidden = ['password', 'remember_token'];

    public function __construct(array $attributes = [])
    {
        $this->connection = config('tenancy.database.central_connection', 'mysql');
        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        return [
            'ultimo_acceso_en' => 'datetime',
            'password' => 'hashed', // Laravel hashea automático al asignar
        ];
    }
}