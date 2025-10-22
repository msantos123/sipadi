<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     * Como el modelo se llama 'Lote' pero la tabla es 'lotes_estancias',
     * debemos especificarlo aquí.
     *
     * @var string
     */
    protected $table = 'lotes';

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fecha_lote',
        'usuario_registra_id',
        'estado_lote',
        'fecha_envio_operador',
        'fecha_envio_gad',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_lote' => 'date',
        'fecha_envio' => 'datetime',
    ];

    /**
     * Define la relación: Un Lote tiene muchas Estancias.
     */
    public function estancias(): HasMany
    {
        return $this->hasMany(Estancia::class, 'lote_id');
    }

    /**
     * Define la relación: Un Lote pertenece a un Usuario (quien lo registró).
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registra_id');
    }
}
