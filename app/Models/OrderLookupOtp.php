<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLookupOtp extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'attempts',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
