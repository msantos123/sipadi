<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';
    protected $fillable = [
        'persona_buscada_nombre',
        'persona_buscada_identificacion',
        'fecha_solicitud',
        'documento_adjunto_path',
        'resultado_busqueda',
        'usuario_creador_id',
    ];

    public function detallesOrdenJudicial()
    {
        return $this->hasOne(DetallesOrdenJudicial::class);
    }

    public function detallesOrdenOficial()
    {
        return $this->hasOne(DetallesOrdenOficial::class);
    }

    public function detallesRequerimientoFiscal()
    {
        return $this->hasOne(DetallesRequerimientoFiscal::class);
    }
}
