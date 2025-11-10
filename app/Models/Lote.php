<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    use HasFactory;

    protected $table = 'lotes';

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fecha_lote',
        'estado_lote',
        'establecimiento_id',
        'sucursal_id',
        'departamento_id',
        'usuario_registra_id',
        'operador_envio_id',
        'fecha_envio_gad',
        'fecha_envio_nacional',
        'aprobador_gad_id',
        'fecha_envio_completado',
        'aprobador_nacional_id',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_lote' => 'date',
        'fecha_envio_operador' => 'datetime',
        'fecha_envio_gad' => 'datetime',
    ];

    /**
     * Un Lote pertenece a un Establecimiento.
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id', 'id_establecimiento');
    }

    /**
     * Un Lote pertenece a una Sucursal.
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id', 'id_sucursal');
    }

    /**
     * Un Lote pertenece a un Departamento.
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Un Lote es registrado por un Usuario.
     */
    public function usuarioRegistra(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registra_id');
    }

    /**
     * Un Lote es enviado por un Usuario (operador).
     */
    public function operadorEnvio(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_envio_id');
    }

    /**
     * Un Lote tiene muchas Estancias.
     */
    public function estancias(): HasMany
    {
        return $this->hasMany(Estancia::class, 'lote_id');
    }

    public function sucursales()
    {
        return $this->hasMany(Sucursal::class, 'id_casa_matriz', 'establecimiento_id');
    }
}
