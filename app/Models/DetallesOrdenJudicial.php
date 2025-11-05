<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallesOrdenJudicial extends Model
{

    protected $table = 'detalles_orden_judicial';
    protected $fillable = [
        'nombre_juzgado_tribunal',
        'numero_orden_judicial',
        'solicitud_id',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }
}
