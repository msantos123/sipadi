<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    protected $connection = 'mysql_sidetur';
    protected $table = 'establecimiento';
    protected $primaryKey = 'id_establecimiento';

    protected $fillable = [
        'id_establecimiento',
        'id_usuario',
        'id_tipo_prestador',
        'id_prestador',
        'id_departamento',
        'codigo',
        'id_categorizacion',
        'razon_social',
        'municipio',
        'comunidad',
        'direccion_establecimiento',
        'telefono',
        'celular',
        'estado_activo',
    ];


}
