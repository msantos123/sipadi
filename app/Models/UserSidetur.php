<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSidetur extends Model
{
    protected $connection = 'mysql_sidetur';
    protected $table = 'users';

    protected $fillable = [
        'id',
        'ip_address',
        'username',
        'password',
        'email',
        'created_on',
        'last_login',
        'active',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'fecha_nacimiento',
        'genero',
        'cedula_identidad',
        'complemento',
        'extension',
        'departamento',
        'direccion_domicilio',
        'tlefono_domiciliario',
        'numero_celular',
        'ciudad',
        'cargo',
        'estado',
    ];
}
