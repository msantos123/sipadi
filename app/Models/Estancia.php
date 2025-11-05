<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estancia extends Model
{
    use HasFactory;

    protected $fillable = [
        'reserva_id',
        'persona_id',
        'responsable_id',
        'nro_cuarto',
        'fecha_hora_ingreso',
        'fecha_hora_salida_efectiva',
        'estado_estancia',
        'es_titular',
        'tipo_parentesco',
        'lote_id',
        'tipo_cuarto_id',
    ];

    protected $casts = [
        'fecha_hora_ingreso' => 'datetime',
        'fecha_hora_salida_efectiva' => 'datetime',
        'gad_fecha_aprobacion' => 'datetime',
        'vmt_fecha_aprobacion' => 'datetime',
    ];

    // --- RELACIONES ---

    public function persona() { return $this->belongsTo(Persona::class); }
    public function reserva() { return $this->belongsTo(Reserva::class); }

    public function lote() { return $this->belongsTo(Lote::class, 'lote_id'); }

    // Titular responsable de esta estancia (si es un dependiente)
    public function responsable() { return $this->belongsTo(Estancia::class, 'responsable_id'); }

    // Dependientes asociados a esta estancia (si es un titular)
    public function dependientes() { return $this->hasMany(Estancia::class, 'responsable_id'); }

    // Usuario GAD que aprobó/rechazó
    public function aprobadorGad() { return $this->belongsTo(Usuario::class, 'gad_usuario_id'); }

    // Usuario VMT que aprobó/rechazó
    public function aprobadorVmt() { return $this->belongsTo(Usuario::class, 'vmt_usuario_id'); }

    // --- SCOPES PARA FACILITAR CONSULTAS ---

    public function scopePendientesGad(Builder $query): void
    {
        $query->where('estado_aprobacion_gad', 'PENDIENTE');
    }

    public function scopePendientesVmt(Builder $query): void
    {
        $query->where('estado_aprobacion_gad', 'APROBADO')
              ->where('estado_aprobacion_vmt', 'PENDIENTE');
    }

    public function tipoCuarto()
    {
        return $this->belongsTo(TipoCuarto::class, 'tipo_cuarto_id');
    }
}
