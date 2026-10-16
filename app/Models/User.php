<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
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
