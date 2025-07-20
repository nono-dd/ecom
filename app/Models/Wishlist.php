<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'added_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Boot method pour définir automatiquement added_at
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wishlist) {
            if (!$wishlist->added_at) {
                $wishlist->added_at = now();
            }
        });
    }

    // Scope pour les articles ajoutés récemment
    public function scopeRecentlyAdded($query, int $days = 7)
    {
        return $query->where('added_at', '>=', now()->subDays($days));
    }
}
