<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Log;
use App\Models\Exception;



/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property null|\Illuminate\Support\Carbon $email_verified_at
 * @property string $password
 * @property null|string $two_factor_secret
 * @property null|string $two_factor_recovery_codes
 * @property null|string $two_factor_confirmed_at
 * @property null|string $remember_token
 * @property null|\Illuminate\Support\Carbon $created_at
 * @property null|\Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property null|int $notifications_count
 * @property string $profile_photo_url
 * @property \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property null|int $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens;

    use HasRoles; // pour les rôles avec spacie

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    // use Exception;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'shipping_contry',
        'shipping_city',
        'shipping_postal_code',
        'profile_photo_path',
        'billing_address',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }


    protected static function boot()
    {
        parent::boot();

        // 1. Assigner un rôle par défaut (ton exemple)
        static::created(function ($user) {
            try {
                $user->assignRole('user');
            } catch (\Exception $e) {
                \Log::error('Impossible d\'assigner le rôle user: ' . $e->getMessage());
            }
        });
    }

    // ========== MUTATEURS ==========

    /**
     * Mutateur pour le nom : première lettre en majuscule
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords(strtolower($value));
    }

    /**
     * Mutateur pour l'email : toujours en minuscules
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    /**
     * Mutateur pour le code pays : toujours en majuscules
     */
    public function setShippingCountryAttribute($value)
    {
        $this->attributes['shipping_country'] = $value ? strtoupper($value) : null;
    }

    /**
     * Mutateur pour la ville : première lettre en majuscule
     */
    public function setShippingCityAttribute($value)
    {
        $this->attributes['shipping_city'] = $value ? ucwords(strtolower($value)) : null;
    }

    // ========== ACCESSEURS ==========

    /**
     * Accesseur pour l'adresse complète de livraison
     */
    public function getFullShippingAddressAttribute()
    {
        $parts = array_filter([
            $this->shipping_city,
            $this->shipping_postal_code,
            $this->getCountryName()
        ]);

        return implode(', ', $parts);
    }

    /**
     * Accesseur pour les initiales
     */
    public function getInitialsAttribute()
    {
        $names = explode(' ', $this->name);
        $initials = '';

        foreach ($names as $name) {
            $initials .= substr($name, 0, 1);
        }

        return strtoupper($initials);
    }

    /**
     * Accesseur pour l'URL de la photo de profil
     */
    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : $this->getDefaultProfilePhotoUrl();
    }

    // ========== MÉTHODES MÉTIER ==========

    /**
     * Vérifier si l'utilisateur a une adresse de livraison complète
     */
    public function hasCompleteShippingAddress()
    {
        return !empty($this->shipping_country) &&
            !empty($this->shipping_city) &&
            !empty($this->shipping_postal_code);
    }

    /**
     * Vérifier si l'utilisateur a un profil complet
     */
    public function hasCompleteProfile()
    {
        return !empty($this->name) &&
            !empty($this->email) &&
            !empty($this->phone) &&
            $this->hasCompleteShippingAddress();
    }

    /**
     * Obtenir le nom du pays depuis le code ISO
     */
    public function getCountryName()
    {
        $countries = [
            'FR' => 'France',
            'BE' => 'Belgique',
            'CH' => 'Suisse',
            'CA' => 'Canada',
            'US' => 'États-Unis',
            'GB' => 'Royaume-Uni',
            'DE' => 'Allemagne',
            'ES' => 'Espagne',
            'IT' => 'Italie',
            'BF' => 'Burkina Faso',
            // Ajouter d'autres pays selon tes besoins
        ];

        return $countries[$this->shipping_country] ?? $this->shipping_country;
    }

    /**
     * Obtenir l'URL par défaut de la photo de profil
     */
    protected function getDefaultProfilePhotoUrl()
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Formater le numéro de téléphone
     */
    public function getFormattedPhoneAttribute()
    {
        if (!$this->phone) {
            return null;
        }

        // Exemple de formatage simple
        return preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $this->phone);
    }

    // ========== SCOPES ==========

    /**
     * Scope pour les utilisateurs avec adresse complète
     */
    public function scopeWithCompleteAddress($query)
    {
        return $query->whereNotNull('shipping_country')
            ->whereNotNull('shipping_city')
            ->whereNotNull('shipping_postal_code');
    }

    /**
     * Scope pour rechercher par pays
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('shipping_country', strtoupper($country));
    }

    /**
     * Scope pour rechercher par ville
     */
    public function scopeByCity($query, $city)
    {
        return $query->where('shipping_city', 'like', '%' . $city . '%');
    }

    /**
     * Scope pour les utilisateurs vérifiés
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    // ========== RELATIONS ==========

    /**
     * Relation avec les commandes (exemple)
     */
    public function orders()
    {
        // return $this->hasMany(Order::class);
    }

    /**
     * Relation avec les sessions
     */
    public function sessions()
    {
        // return $this->hasMany(Session::class);
    }

    // ========== MÉTHODES DE VALIDATION ==========

    /**
     * Règles de validation
     */
    public static function validationRules($id = null)
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users,email,' . $id,
            'password' => $id ? 'nullable|min:8' : 'required|min:8',
            'shipping_country' => 'nullable|string|size:2',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:23',
            'billing_address' => 'nullable|string',
            'phone' => 'nullable|string|max:30',
            'profile_photo_path' => 'nullable|string|max:2048',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    public static function validationMessages()
    {
        return [
            'name.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'shipping_country.size' => 'Le code pays doit contenir exactement 2 caractères',
        ];
    }
}
