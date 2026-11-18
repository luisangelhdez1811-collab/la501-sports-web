<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Suscriptor del boletín de promociones. Doble confirmación: quien se inscribe sin cuenta recibe un
 * correo y solo queda confirmado al abrir el enlace, así nadie puede inscribir un correo ajeno. Quien lo
 * activa desde su propia cuenta (registro o Configuración) ya está confirmado.
 */
class PromoSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'user_id',
        'unsubscribe_token',
        'confirmed_at',
        'confirm_token',
    ];

    protected $hidden = ['unsubscribe_token', 'confirm_token'];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PromoSubscriber $suscriptor) {
            if (!$suscriptor->unsubscribe_token) {
                $suscriptor->unsubscribe_token = Str::random(48);
            }

            // Activado por el titular desde su cuenta: no hace falta confirmar por correo
            if ($suscriptor->user_id && !$suscriptor->confirmed_at) {
                $suscriptor->confirmed_at = now();
            }
        });
    }

    /** Solo a estos se les mandan promociones. */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
