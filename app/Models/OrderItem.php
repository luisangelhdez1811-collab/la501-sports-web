<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'subtotal',
        'excluded_ingredients',
        'notes'
    ];

    protected $casts = [
        'excluded_ingredients' => 'array'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Agregamos la relación hacia el producto
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
