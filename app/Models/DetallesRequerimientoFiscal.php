<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallesRequerimientoFiscal extends Model
{
    protected $table = 'detalles_requerimiento_fiscal';
    protected $fillable = [
        'solicitud_id',
        'fiscal_apellidos_nombres',
        'fiscal_de_materia',
        'numero_de_caso',
        'solicitante_apellidos_nombres',
        'solicitante_identificacion',

    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }
}
