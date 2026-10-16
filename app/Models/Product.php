<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    protected $fillable = ['name', 'description', 'price', 'category','subcategory', 'image', 'available'];
    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('la501_products_active');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('la501_products_active');
        });
    }
    public function getImageAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }
        if (str_contains($value, 'res.cloudinary.com') && str_contains($value, '/upload/')) {
            if (!str_contains($value, 'q_auto')) {
                return str_replace('/upload/', '/upload/f_auto,q_auto/', $value);
            }
        }
        return $value;
    }
    public function ingredientes()
    {
        return $this->hasMany(Ingrediente::class, 'product_id');
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'promotion_product')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}