<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    public function calculateTotalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            if ($orderItem->quantity && $orderItem->unit_price) {
                $orderItem->total_price = $orderItem->quantity * $orderItem->unit_price;
            }
        });
    }
}
