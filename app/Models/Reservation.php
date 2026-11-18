<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_completo',
        'telefono',
        'correo_electronico',
        'fecha_reservacion',
        'hora_reservacion',
        'cantidad_personas',
        'zona',
        'status',
        'privacy_accepted_at',
        'privacy_version',
    ];

    protected $casts = [
        'fecha_reservacion' => 'date',
    ];
}
