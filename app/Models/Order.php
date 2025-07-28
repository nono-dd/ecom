<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Statuts possibles des commandes
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Devises supportées
     */
    public const CURRENCY_EUR = 'EUR';
    public const CURRENCY_USD = 'USD';
    public const CURRENCY_GBP = 'GBP';
    public const CURRENCY_FCFA = 'FCFA';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'shipping',
        'tax',
        'total',
        'currency',
        'guest_email',
        'guest_name',
        'payment_method',
        'payment_id',
        'paid_at',
        'billing_address',
        'shipping_address',
        'notes',
        'custom_fields',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'custom_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method pour les événements du modèle
     */
    protected static function boot()
    {
        parent::boot();

        // Générer automatiquement un numéro de commande
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = $order->generateOrderNumber();
            }

            // Définir la devise par défaut
            if (empty($order->currency)) {
                $order->currency = self::CURRENCY_FCFA;
            }
        });

        // Logger les changements de statut
        static::updated(function ($order) {
            if ($order->wasChanged('status')) {
                \Log::info("Commande {$order->order_number} : statut changé vers {$order->status}");

                // Déclencher des événements selon le statut
                $order->handleStatusChange();
            }
        });
    }

    // ========== MUTATEURS ==========

    /**
     * Mutateur pour l'email invité : toujours en minuscules
     */
    public function setGuestEmailAttribute($value)
    {
        $this->attributes['guest_email'] = $value ? strtolower(trim($value)) : null;
    }

    /**
     * Mutateur pour le nom invité : format titre
     */
    public function setGuestNameAttribute($value)
    {
        $this->attributes['guest_name'] = $value ? ucwords(strtolower(trim($value))) : null;
    }

    /**
     * Mutateur pour les totaux : s'assurer qu'ils sont positifs
     */
    public function setSubtotalAttribute($value)
    {
        $this->attributes['subtotal'] = max(0, (float) $value);
    }

    public function setShippingAttribute($value)
    {
        $this->attributes['shipping'] = max(0, (float) $value);
    }

    public function setTaxAttribute($value)
    {
        $this->attributes['tax'] = max(0, (float) $value);
    }

    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = max(0, (float) $value);
    }

    // ========== ACCESSEURS ==========

    /**
     * Accesseur pour le nom du client (utilisateur ou invité)
     */
    public function getCustomerNameAttribute()
    {
        return $this->user ? $this->user->name : $this->guest_name;
    }

    /**
     * Accesseur pour l'email du client (utilisateur ou invité)
     */
    public function getCustomerEmailAttribute()
    {
        return $this->user ? $this->user->email : $this->guest_email;
    }

    /**
     * Accesseur pour le statut formaté
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CONFIRMED => 'Confirmée',
            self::STATUS_PROCESSING => 'En préparation',
            self::STATUS_SHIPPED => 'Expédiée',
            self::STATUS_DELIVERED => 'Livrée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_REFUNDED => 'Remboursée',
            default => 'Inconnu'
        };
    }

    /**
     * Accesseur pour la couleur du statut (pour l'interface)
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_CONFIRMED => 'blue',
            self::STATUS_PROCESSING => 'orange',
            self::STATUS_SHIPPED => 'purple',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_REFUNDED => 'gray',
            default => 'gray'
        };
    }

    /**
     * Accesseur pour l'adresse de facturation formatée
     */
    public function getFormattedBillingAddressAttribute()
    {
        return $this->formatAddress($this->billing_address);
    }

    /**
     * Accesseur pour l'adresse de livraison formatée
     */
    public function getFormattedShippingAddressAttribute()
    {
        return $this->formatAddress($this->shipping_address);
    }

    /**
     * Accesseur pour vérifier si la commande est payée
     */
    public function getIsPaidAttribute()
    {
        return !is_null($this->paid_at);
    }

    /**
     * Accesseur pour vérifier si la commande peut être annulée
     */
    public function getCanBeCancelledAttribute()
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
        ]);
    }

    /**
     * Accesseur pour vérifier si la commande peut être remboursée
     */
    public function getCanBeRefundedAttribute()
    {
        return $this->is_paid && in_array($this->status, [
            self::STATUS_CONFIRMED,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
        ]);
    }

    // ========== RELATIONS ==========

    /**
     * Relation : utilisateur qui a passé la commande
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : articles de la commande
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relation : historique des statuts
     */
    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    /**
     * Relation : paiements associés
     */
    // public function payments()
    // {
    //     return $this->hasMany(Payment::class);
    // }

    /**
     * Relation : expéditions
     */
    // public function shipments()
    // {
    //     return $this->hasMany(Shipment::class);
    // }

    // ========== SCOPES ==========

    /**
     * Scope pour les commandes par statut
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour les commandes en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope pour les commandes confirmées
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope pour les commandes payées
     */
    public function scopePaid($query)
    {
        return $query->whereNotNull('paid_at');
    }

    /**
     * Scope pour les commandes non payées
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNull('paid_at');
    }

    /**
     * Scope pour les commandes d'un client
     */
    public function scopeForCustomer($query, $email)
    {
        return $query->where(function ($q) use ($email) {
            $q->whereHas('user', function ($userQuery) use ($email) {
                $userQuery->where('email', $email);
            })->orWhere('guest_email', $email);
        });
    }

    /**
     * Scope pour recherche par numéro de commande
     */
    public function scopeByOrderNumber($query, $orderNumber)
    {
        return $query->where('order_number', 'like', '%' . $orderNumber . '%');
    }

    /**
     * Scope pour les commandes récentes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour les commandes par période
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // ========== MÉTHODES MÉTIER ==========

    /**
     * Vérifier si c'est une commande invitée
     */
    public function isGuestOrder()
    {
        return is_null($this->user_id);
    }

    /**
     * Marquer la commande comme payée
     */
    public function markAsPaid($paymentMethod = null, $paymentId = null)
    {
        $this->update([
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'payment_id' => $paymentId,
            'status' => self::STATUS_CONFIRMED,
        ]);

        return $this;
    }

    /**
     * Changer le statut de la commande
     */
    public function updateStatus($newStatus, $notes = null)
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => $newStatus,
            'notes' => $notes ? ($this->notes . "\n" . $notes) : $this->notes,
        ]);

        // Enregistrer dans l'historique
        $this->recordStatusChange($oldStatus, $newStatus, $notes);

        return $this;
    }

    /**
     * Annuler la commande
     */
    public function cancel($reason = null)
    {
        if (!$this->can_be_cancelled) {
            throw new \Exception('Cette commande ne peut pas être annulée');
        }

        return $this->updateStatus(self::STATUS_CANCELLED, $reason);
    }

    /**
     * Rembourser la commande
     */
    public function refund($reason = null)
    {
        if (!$this->can_be_refunded) {
            throw new \Exception('Cette commande ne peut pas être remboursée');
        }

        return $this->updateStatus(self::STATUS_REFUNDED, $reason);
    }

    /**
     * Calculer le total de la commande
     */
    public function calculateTotal()
    {
        $this->subtotal = $this->orderItems()->sum(\DB::raw('quantity * price'));
        $this->total = $this->subtotal + $this->shipping + $this->tax;
        $this->save();

        return $this;
    }

    /**
     * Obtenir le nombre total d'articles
     */
    public function getTotalItemsCount()
    {
        return $this->orderItems()->sum('quantity');
    }

    /**
     * Vérifier si tous les articles sont en stock
     */
    public function hasAllItemsInStock()
    {
        foreach ($this->orderItems as $item) {
            if ($item->product && $item->product->stock < $item->quantity) {
                return false;
            }
        }
        return true;
    }

    // ========== MÉTHODES PRIVÉES ==========

    /**
     * Générer un numéro de commande unique
     */
    private function generateOrderNumber()
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');

        // Obtenir le dernier numéro du jour
        $lastOrder = self::where('order_number', 'like', $prefix . $date . '%')
                         ->orderBy('order_number', 'desc')
                         ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . $date . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Formater une adresse
     */
    private function formatAddress($address)
    {
        if (!$address || !is_array($address)) {
            return null;
        }

        $parts = array_filter([
            $address['name'] ?? null,
            $address['company'] ?? null,
            $address['address_line_1'] ?? null,
            $address['address_line_2'] ?? null,
            trim(($address['postal_code'] ?? '') . ' ' . ($address['city'] ?? '')),
            $address['country'] ?? null,
        ]);

        return implode("\n", $parts);
    }

    /**
     * Enregistrer un changement de statut dans l'historique
     */
    private function recordStatusChange($oldStatus, $newStatus, $notes = null)
    {
        // Si vous avez une table d'historique des statuts
        // OrderStatusHistory::create([
        //     'order_id' => $this->id,
        //     'old_status' => $oldStatus,
        //     'new_status' => $newStatus,
        //     'notes' => $notes,
        //     'changed_by' => auth()->id(),
        // ]);
    }

    /**
     * Gérer les actions selon le changement de statut
     */
    private function handleStatusChange()
    {
        switch ($this->status) {
            case self::STATUS_CONFIRMED:
                // Envoyer email de confirmation
                // $this->notify(new OrderConfirmedNotification());
                break;

            case self::STATUS_SHIPPED:
                // Envoyer email d'expédition
                // $this->notify(new OrderShippedNotification());
                break;

            case self::STATUS_DELIVERED:
                // Envoyer email de livraison
                // $this->notify(new OrderDeliveredNotification());
                break;

            case self::STATUS_CANCELLED:
                // Remettre en stock les produits
                // $this->restoreStock();
                break;
        }
    }

    // ========== MÉTHODES STATIQUES ==========

    /**
     * Obtenir tous les statuts disponibles
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CONFIRMED => 'Confirmée',
            self::STATUS_PROCESSING => 'En préparation',
            self::STATUS_SHIPPED => 'Expédiée',
            self::STATUS_DELIVERED => 'Livrée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_REFUNDED => 'Remboursée',
        ];
    }

    /**
     * Statistiques des commandes
     */
    public static function getStatistics($period = 30)
    {
        $startDate = now()->subDays($period);

        return [
            'total_orders' => self::where('created_at', '>=', $startDate)->count(),
            'total_revenue' => self::where('created_at', '>=', $startDate)->sum('total'),
            'average_order_value' => self::where('created_at', '>=', $startDate)->avg('total'),
            'pending_orders' => self::pending()->count(),
            'paid_orders' => self::paid()->where('created_at', '>=', $startDate)->count(),
        ];
    }

    // ========== VALIDATION ==========

    /**
     * Règles de validation
     */
    public static function validationRules($id = null)
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'order_number' => 'nullable|string|max:30|unique:orders,order_number,' . $id,
            'status' => 'required|in:' . implode(',', array_keys(self::getStatuses())),
            'subtotal' => 'required|numeric|min:0',
            'shipping' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'guest_email' => 'required_without:user_id|email|nullable',
            'guest_name' => 'required_without:user_id|string|max:255|nullable',
            'payment_method' => 'nullable|string|max:50',
            'payment_id' => 'nullable|string|max:100',
            'billing_address' => 'nullable|array',
            'shipping_address' => 'nullable|array',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    public static function validationMessages()
    {
        return [
            'guest_email.required_without' => 'L\'email est requis pour les commandes sans compte',
            'guest_name.required_without' => 'Le nom est requis pour les commandes sans compte',
            'subtotal.min' => 'Le sous-total doit être positif',
            'total.min' => 'Le total doit être positif',
            'currency.size' => 'La devise doit être un code à 3 lettres',
        ];
    }
}
