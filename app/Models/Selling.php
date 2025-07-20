<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Selling extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'price',
        'stock_quantity',
        'status',
        'description',
        'condition',
        'listed_at',
        'sold_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'listed_at' => 'datetime',
        'sold_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constantes pour les statuts
    const STATUS_ACTIVE = 'active';
    const STATUS_SOLD = 'sold';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';

    // Constantes pour les conditions
    const CONDITION_NEW = 'new';
    const CONDITION_LIKE_NEW = 'like_new';
    const CONDITION_GOOD = 'good';
    const CONDITION_FAIR = 'fair';
    const CONDITION_POOR = 'poor';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Boot method pour définir automatiquement listed_at
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($selling) {
            if (!$selling->listed_at) {
                $selling->listed_at = now();
            }
            if (!$selling->status) {
                $selling->status = self::STATUS_ACTIVE;
            }
        });
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeSold($query)
    {
        return $query->where('status', self::STATUS_SOLD);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeByCondition($query, string $condition)
    {
        return $query->where('condition', $condition);
    }

    // Méthodes utilitaires
    public function markAsSold(): bool
    {
        return $this->update([
            'status' => self::STATUS_SOLD,
            'sold_at' => now(),
            'stock_quantity' => 0,
        ]);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->stock_quantity > 0;
    }

    public function getStatusArray(): array
    {
        return [
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_SOLD => 'Vendu',
            self::STATUS_INACTIVE => 'Inactif',
            self::STATUS_PENDING => 'En attente',
        ];
    }

    public function getConditionArray(): array
    {
        return [
            self::CONDITION_NEW => 'Neuf',
            self::CONDITION_LIKE_NEW => 'Comme neuf',
            self::CONDITION_GOOD => 'Bon état',
            self::CONDITION_FAIR => 'État correct',
            self::CONDITION_POOR => 'Mauvais état',
        ];
    }
}
