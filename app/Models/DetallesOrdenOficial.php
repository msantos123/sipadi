<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallesOrdenOficial extends Model
{
    // Define la tabla si el nombre no sigue la convención de Laravel
    protected $table = 'detalles_orden_oficial';

    // Indica los campos que pueden llenarse masivamente
    protected $fillable = [
        'institucion',
        'solicitud_id',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }
}
