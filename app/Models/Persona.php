<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'tipo_documento',
        'nro_documento',
        'complemento',
        'fecha_nacimiento',
        'nacionalidad_id',
        'departamento_id',
        'municipio_id',
        'ciudad_origen',
        'sexo',
        'estado_civil',
        'ocupacion',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Relación con nacionalidad
     */
    public function nacionalidad(): BelongsTo
    {
        return $this->belongsTo(Nacionalidad::class);
    }

    /**
     * Relación con departamento (para bolivianos)
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Relación con municipio (para bolivianos)
     */
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    /**
     * Relación con estancias
     */
    public function estancias(): HasMany
    {
        return $this->hasMany(Estancia::class);
    }

    /**
     * Relación con estancias como responsable
     */
    public function estanciasResponsable(): HasMany
    {
        return $this->hasMany(Estancia::class, 'responsable_id');
    }

    /**
     * Accessor para nombre completo
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}");
    }
}
