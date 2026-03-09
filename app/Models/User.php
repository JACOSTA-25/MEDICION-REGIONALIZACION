<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_LIDER_PROCESO = 'LIDER_PROCESO';
    public const ROLE_LIDER_DEPENDENCIA = 'LIDER_DEPENDENCIA';
    public const ROLE_ADMIN_2_0 = 'ADMIN_2_0';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'nombre',
        'password_hash',
        'rol',
        'id_proceso',
        'id_dependencia',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
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
            'password_hash' => 'hashed',
            'activo' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Fortify/Auth should validate against the legacy password column.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * Backward compatibility for starter-kit views that expect "name".
     */
    public function getNameAttribute(): string
    {
        return (string) $this->nombre;
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        return Str::of((string) $this->nombre)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_proceso');
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'id_dependencia', 'id_dependencia');
    }

    public function isAdmin(): bool
    {
        return $this->rol === self::ROLE_ADMIN;
    }

    public function isLiderProceso(): bool
    {
        return $this->rol === self::ROLE_LIDER_PROCESO;
    }

    public function isLiderDependencia(): bool
    {
        return $this->rol === self::ROLE_LIDER_DEPENDENCIA;
    }

    public function isAdmin20(): bool
    {
        return in_array($this->rol, [
            self::ROLE_ADMIN_2_0,
            'ADMINISTRADOR_2_0',
            'ADMINISTRADOR_2.0',
            'ADMINISTRADOR 2.0',
        ], true);
    }

    public function canAccessUsersModule(): bool
    {
        return $this->isAdmin();
    }

    public function canAccessProcessDependencyModule(): bool
    {
        return $this->isAdmin() || $this->isAdmin20();
    }

    public function canAccessGeneralReports(): bool
    {
        return $this->isAdmin() || $this->isAdmin20();
    }

    public function canAccessProcessReports(): bool
    {
        return $this->isAdmin() || $this->isLiderProceso();
    }

    public function canAccessIndividualReports(): bool
    {
        return $this->isAdmin() || $this->isLiderDependencia();
    }
}
