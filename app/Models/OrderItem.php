<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    // En qué va cada línea, para cocina/barra y para el mesero
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_SERVED = 'served';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'station',
        'quantity',
        'price',
        'subtotal',
        'excluded_ingredients',
        'options',
        'notes',
        'prepared_at',
        'served_at',
    ];

    protected $casts = [
        'excluded_ingredients' => 'array',
        'options' => 'array',
        'prepared_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    protected static function booted()
    {
        // La estación se copia del producto al momento del pedido (si después se cambia en el
        // admin, lo que ya está en cocina no se mueve de pantalla). Lo que no se prepara queda
        // listo desde el inicio para que el mesero lo vea como "por entregar".
        static::creating(function (OrderItem $item) {
            if (empty($item->station)) {
                $item->station = optional($item->product)->station ?: Product::STATION_KITCHEN;
            }
            if ($item->station === Product::STATION_NONE && !$item->prepared_at) {
                $item->prepared_at = now();
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Agregamos la relación hacia el producto
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Las líneas anteriores a la separación cocina/barra no tienen estación: eran de cocina. */
    public function stationOrDefault(): string
    {
        return $this->station ?: Product::STATION_KITCHEN;
    }

    public function prepStatus(): string
    {
        if ($this->served_at) {
            return self::STATUS_SERVED;
        }

        return $this->prepared_at ? self::STATUS_READY : self::STATUS_PREPARING;
    }

    /** Opciones elegidas en texto corto: "Con papas · Salsa: Buffalo, Macha". */
    public function optionsSummary(): string
    {
        return collect($this->options ?? [])
            ->groupBy('group')
            ->map(function ($elegidas, $grupo) {
                $nombres = $elegidas->map(fn ($o) => (($o['quantity'] ?? 1) > 1 ? $o['quantity'] . 'x ' : '') . $o['name'])->implode(', ');
                return $grupo !== '' ? $grupo . ': ' . $nombres : $nombres;
            })
            ->implode(' · ');
    }
}
