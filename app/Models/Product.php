<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'brand',
        'price',
        'cost_price',
        'sale_price',
        'sale_starts_at',
        'sale_ends_at',
        'stock',
        'min_stock',
        'sku',
        'barcode',
        'weight',
        'length',
        'width',
        'height',
        'meta_title',
        'meta_description',
        'is_active',
        'is_featured',
        'track_inventory',
        'status',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'track_inventory' => 'boolean',
        'published_at' => 'datetime',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
    ];

    // Constantes pour les statuts
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Relations
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function sellings(): HasMany
    {
        return $this->hasMany(Selling::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Boot method pour générer automatiquement le slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'SKU-' . strtoupper(Str::random(8));
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->getOriginal('slug'))) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
                    ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock')
                    ->where('track_inventory', true);
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price')
                    ->where(function($q) {
                        $q->whereNull('sale_starts_at')
                          ->orWhere('sale_starts_at', '<=', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('sale_ends_at')
                          ->orWhere('sale_ends_at', '>=', now());
                    });
    }

    public function scopeByBrand($query, string $brand)
    {
        return $query->where('brand', $brand);
    }

    public function scopePriceBetween($query, float $min, float $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Accesseurs
     */
    public function getCurrentPriceAttribute(): float
    {
        if ($this->isOnSale()) {
            return $this->sale_price;
        }
        return $this->price;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->isOnSale()) {
            return null;
        }
        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->count();
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->track_inventory && $this->stock <= $this->min_stock;
    }

    public function getVolumeAttribute(): ?float
    {
        if ($this->length && $this->width && $this->height) {
            return ($this->length * $this->width * $this->height) / 1000; // en litres
        }
        return null;
    }

    /**
     * Méthodes utilitaires
     */
    public function isOnSale(): bool
    {
        if (!$this->sale_price) {
            return false;
        }

        $now = now();

        $saleStarted = !$this->sale_starts_at || $this->sale_starts_at <= $now;
        $saleNotEnded = !$this->sale_ends_at || $this->sale_ends_at >= $now;

        return $saleStarted && $saleNotEnded;
    }

    public function isInStock(): bool
    {
        return !$this->track_inventory || $this->stock > 0;
    }

    public function canBePurchased(): bool
    {
        return $this->is_active &&
               $this->status === self::STATUS_PUBLISHED &&
               $this->isInStock() &&
               ($this->published_at === null || $this->published_at <= now());
    }

    public function decreaseStock(int $quantity): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        if ($this->stock < $quantity) {
            return false;
        }

        return $this->update(['stock' => $this->stock - $quantity]);
    }

    public function increaseStock(int $quantity): bool
    {
        return $this->update(['stock' => $this->stock + $quantity]);
    }

    public function publish(): bool
    {
        return $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
            'is_active' => true,
        ]);
    }

    public function archive(): bool
    {
        return $this->update([
            'status' => self::STATUS_ARCHIVED,
            'is_active' => false,
        ]);
    }

    /**
     * Méthodes statiques utiles
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PUBLISHED => 'Publié',
            self::STATUS_ARCHIVED => 'Archivé',
        ];
    }

    public static function generateUniqueSku(): string
    {
        do {
            $sku = 'SKU-' . strtoupper(Str::random(8));
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Route model binding par slug
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
