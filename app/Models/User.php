<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'apellido_paterno',
        'apellido_materno',
        'nombres',
        'ci',
        'celular',
        'nacionalidad_id',
        'departamento_id',
        'municipio_id',
        'establecimiento_id',
        'sucursal_id',
        'estado',
        'email',
        'password',
        'verification_code',
        'verification_code_expires_at',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id', 'id_establecimiento');
    }

    public function establecimientos()
    {
        return $this->hasMany(Establecimiento::class, 'id_usuario', 'id');
    }


    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id', 'id_sucursal');
    }

    /**
     * Genera un código de verificación aleatorio de 8 caracteres
     * y establece su fecha de expiración a 24 horas desde ahora.
     *
     * @return string El código de verificación generado
     */
    public function generateVerificationCode(): string
    {
        $this->verification_code = strtoupper(\Illuminate\Support\Str::random(8));
        $this->verification_code_expires_at = now()->addHours(24);
        $this->save();

        return $this->verification_code;
    }

    /**
     * Verifica si el código de verificación es válido y no ha expirado.
     *
     * @param string $code
     * @return bool
     */
    public function isVerificationCodeValid(string $code): bool
    {
        return $this->verification_code === strtoupper($code)
            && $this->verification_code_expires_at
            && $this->verification_code_expires_at->isFuture();
    }
}
