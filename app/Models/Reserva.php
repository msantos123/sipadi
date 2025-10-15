<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserva extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_reserva',
        'establecimiento_id',
        'usuario_registra_id',
        'fecha_entrada',
        'fecha_salida',
    ];

    protected $casts = [
        'fecha_entrada' => 'date',
        'fecha_salida' => 'date',
    ];

    /**
     * Relación con usuario que registra
     */
    public function usuarioRegistra(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registra_id');
    }

    /**
     * Relación con estancias
     */
    public function estancias(): HasMany
    {
        return $this->hasMany(Estancia::class);
    }

    /**
     * Relación con establecimiento (si existe el modelo)
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }
}
