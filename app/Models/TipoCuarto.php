<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoCuarto extends Model
{
    use HasFactory;

    protected $table = 'tipo_cuartos';

    protected $fillable = [
        'nombre',
        'nro_habitaciones',
        'nro_personas',
        'establecimiento_id',
        'sucursal_id',

    ];

    public function estancias()
    {
        // Eloquent buscará la clave foránea 'tipo_cuarto_id' en la tabla 'estancias'.
        return $this->hasMany(Estancia::class, 'tipo_cuarto_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id', 'id_sucursal');
    }

        public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id', 'id_establecimiento');
    }
}
