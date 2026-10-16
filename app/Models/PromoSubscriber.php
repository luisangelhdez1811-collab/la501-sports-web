<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'user_id',
        'unsubscribe_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
