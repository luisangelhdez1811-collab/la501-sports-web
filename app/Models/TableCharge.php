<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un cobro de mesa hecho desde la app del mesero (en línea o sin conexión).
 * Es la bitácora que permite auditar quién cobró, cuánto, con qué método y si hubo
 * diferencias contra lo que el sistema tenía registrado.
 */
class TableCharge extends Model
{
    public const STATUS_APPLIED = 'applied';
    public const STATUS_DISCREPANCY = 'discrepancy';
    public const STATUS_CONFLICT = 'conflict';

    protected $fillable = [
        'charge_ref',
        'table_number',
        'waiter_id',
        'total_due',
        'amount_received',
        'payments',
        'offline',
        'forced',
        'reason',
        'status',
        'notes',
        'charged_at',
    ];

    protected $casts = [
        'payments' => 'array',
        'offline' => 'boolean',
        'forced' => 'boolean',
        'total_due' => 'float',
        'amount_received' => 'float',
        'charged_at' => 'datetime',
    ];

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
