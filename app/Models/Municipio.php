<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_municipio',
        'codigo_municipio',
        'departamento_id',
    ];

    /**
     * Relación con departamento
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Relación con personas (bolivianos)
     */
    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class);
    }
}
