<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cedula',
        'contacto',
        'rol',
        'rol_id',
        'activo',
        'puede_editar_precios',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'activo' => 'boolean',
            'puede_editar_precios' => 'boolean',
        ];
    }

    // 🔗 RELACIÓN CON ROL
    public function rolRelacion()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    // 🔗 RELACIÓN CON PRODUCCIÓN
    public function producciones()
    {
        return $this->hasMany(Produccion::class);
    }

    // 🔐 FUNCIONES DE ROLES (AGREGADAS)
    public function historialProducciones()
    {
        return $this->hasMany(HistorialProduccion::class);
    }

    public function facturasRecolector()
    {
        return $this->hasMany(FacturaRecolector::class, 'recolector_id');
    }

    public function obtenerRol(): string
    {
        if (! empty($this->rol)) {
            return strtolower((string) $this->rol);
        }

        if ($this->relationLoaded('rolRelacion') || $this->rol_id) {
            return strtolower((string) optional($this->rolRelacion)->nombre);
        }

        return 'usuario';
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->obtenerRol(), array_map('strtolower', $roles), true);
    }

    public function esAdmin(): bool
    {
        return $this->tieneRol('admin');
    }

    public function esProgramador(): bool
    {
        return $this->tieneRol('programador');
    }

    public function esUsuario(): bool
    {
        return $this->tieneRol('usuario');
    }

    public function esRecolector(): bool
    {
        return $this->tieneRol('recolector');
    }

    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }

    public function puedeEditarPrecios(): bool
    {
        return $this->esRecolector() && (bool) $this->puede_editar_precios;
    }
}
