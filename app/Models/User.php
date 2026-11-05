<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'telefono',
        'pregunta_secreta',
        'respuesta_secreta',
        'role',
        'is_active',
        'points',
        'aviso_privacidad_aceptado_at',
        'recomendaciones_personalizadas',
        'promos_sms',
        'preferencias_privacidad_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'aviso_privacidad_aceptado_at' => 'datetime',
            'recomendaciones_personalizadas' => 'boolean',
            'promos_sms' => 'boolean',
            'preferencias_privacidad_at' => 'datetime',
        ];
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * La foto de perfil como URL completa (https en producción). En la base se guarda una ruta relativa
     * ("avatars/av_123.jpg") que la app no puede mostrar; web y app deben ver la misma foto (B-16).
     */
    public function avatarUrl(): ?string
    {
        if (blank($this->avatar)) {
            return null;
        }

        if (preg_match('#^https?://#i', $this->avatar)) {
            return \App\Services\PublicUrl::secure($this->avatar);
        }

        return \App\Services\PublicUrl::forPath($this->avatar);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
                    ->withPivot('unlocked_at')
                    ->withTimestamps();
    }

    public function reservations()
    {
        return $this->hasMany(\App\Models\Reservation::class, 'correo_electronico', 'email');
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class);
    }
}
