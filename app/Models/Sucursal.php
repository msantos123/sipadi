<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sucursal extends Model
{
    protected $connection = 'mysql_sidetur';
    protected $table = 'sucursal';
    protected $primaryKey = 'id_sucursal';

    protected $fillable = [
        'id_sucursal',
        'id_usuario_create',
        'id_casa_matriz',
        'id_departamento',
        'id_prestador',
        'id_tipo_prestador',
        'id_tipo_prestador',
        'id_categorizacion_sucursal',
        'codigo',
        'numero_sucursal',
        'nombre_sucursal',
        'municipio',
        'ciudad',
        'comunidad',
        'direccion_sucursal',
        'telefono',
        'celular',
        'estado_activo',

    ];

    /**
     * Get the departamento that owns the sucursal.
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'id_departamento');
    }
}
