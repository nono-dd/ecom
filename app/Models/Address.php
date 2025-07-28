<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;


class Address extends Model
{
    protected $fillable = [
        'addressable_id',
        'addressable_type',
        'type', // home, work, billing, shipping
        'first_name',
        'last_name',
        'company',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'phone',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constantes pour les types d'adresses
    const TYPE_HOME = 'home';
    const TYPE_WORK = 'work';
    const TYPE_BILLING = 'billing'; // Facturation
    const TYPE_SHIPPING = 'shipping'; // Livraison

    /**
     * Relation polymorphe - une adresse peut appartenir a différents modeles
     * (User, Company, etc.)
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relation avec le pays
     * Utilise country_code comme clé étrangère et code comme clé primaire
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Boot method pour gérer les adresses par défaut
     */
    protected static function boot()
    {
        parent::boot();

        // Quand on sauvegarde une adresse comme défaut
        static::saving(function ($address) {
            if ($address->is_default) {
                // Retirer le statut par défaut des autres adresses du meme propriétaire et type
                static::where('addressable_id', $address->addressable_id)
                    ->where('addressable_type', $address->addressable_type)
                    ->where('type', $address->type)
                    ->where('id', '!=', $address->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Scopes
     */
    public function scopeOfDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeShipping(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SHIPPING);
    }

    public function scopeBilling(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BILLING);
    }

    /**
     * Accesseurs
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country?->name,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Méthodes utilitaires
     */
    public function makeOfDefault(): bool
    {
        return $this->update(['is_default' => true]);
    }

    public function isShipping(): bool
    {
        return $this->type === self::TYPE_SHIPPING;
    }

    public function isBilling(): bool
    {
        return $this->type === self::TYPE_BILLING;
    }

    public function getTypeOptions(): array
    {
        return [
            self::TYPE_HOME => 'Domicile',
            self::TYPE_WORK => 'Travail',
            self::TYPE_BILLING => 'Facturation',
            self::TYPE_SHIPPING => 'Livraison',
        ];
    }

    /**
     * Validation rules
     */
    public static function validationRules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country_code' => 'required|exists:countries,code',
            'type' => 'required|in:' . implode(',', [
                self::TYPE_HOME,
                self::TYPE_WORK,
                self::TYPE_BILLING,
                self::TYPE_SHIPPING
            ]),
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
        ];
    }
}
