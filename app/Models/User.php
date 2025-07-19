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
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;
use Illuminate\Validation\Rule;


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

    use HasRoles; // pour les rôles avec spatie

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

    /**
     * La propriété $appends permet d’ajouter automatiquement des
     * attributs calculés à la sortie JSON ou tableau d’un modèle,
     * sans stocker ces champs en base de données.
     *
     *Principe :
     *  Vous listez dans $appends les noms d’attributs fictifs.
     *  Laravel recherche pour chacun la méthode getXxxAttribute() correspondante.
     *  Lors de la sérialisation (toArray(), toJson()), ces valeurs calculées sont injectées au modèle.
     */
    protected $appends = [
        'profile_photo_url',
        'full_shipping_address',
        'initials',
        'formatted_phone',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    // Permet d'eviter les attaque en masse
    protected $fillable = [
        'name',
        'email',
        'shipping_contry',
        'shipping_city',
        'shipping_postal_code',
        'profile_photo_path',
        'billing_address',
        'phone',
        'email_verified_at',
        'password',
        'remember_token'
    ];

    /**
     * The attributes that should be hidden for serialization->(affichage sous farmat JSON ou autre)
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

    // ========== RELATIONS ==========
    /**
     * Mise en place des differentes relations tout en sachant que:
     * 1- Users have several addresses
     * 2- Users have several orders
     * 3- Users have several reviews
     */

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function sellings()
    {
        return $this->hasMany(Selling::class);
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'addresses', 'user_id', 'country')
            ->distinct();
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    protected static function boot()
    {
        parent::boot();

        // ========== ÉVÉNEMENT : AVANT CRÉATION ==========
        static::creating(function ($user) {
            // Validation des données essentielles
            if (empty($user->name)) {
                throw new \InvalidArgumentException('Le nom est obligatoire');
            }

            if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Format email invalide');
            }

            // Génération d'identifiant unique
            // $user->uuid = $user->uuid ?? \Illuminate\Support\Str::uuid();

            // Journalisation avec contexte
            \Log::channel('user_events')->info('Création utilisateur en cours', [
                'email' => $user->email,
                'ip' => request()->ip()
            ]);
        });

        // ========== ÉVÉNEMENT : APRÈS CRÉATION ==========
        static::created(function ($user) {
            try {
                // Assignation du rôle avec configuration
                $defaultRole = config('permission.default_role', 'user');
                $user->assignRole($defaultRole);

                // Création asynchrone de ressources
                \App\Jobs\CreateUserStorageJob::dispatch($user->id);

                if (config('app.send_welcome_emails')) {
                    \App\Jobs\SendWelcomeEmailJob::dispatch($user);
                }

                // Journalisation succès
                \Log::channel('user_events')->info('Utilisateur créé', [
                    'id' => $user->id,
                    'roles' => $user->getRoleNames()
                ]);
            } catch (\Exception $e) {
                \Log::channel('user_errors')->error('Échec création utilisateur', [
                    'id' => $user->id,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e)
                ]);

                // Notification aux administrateurs
                \App\Events\UserCreationFailed::dispatch($user, $e);
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
        return collect(explode(' ', $this->name))
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
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

    /**
     * Accesseur pour les pays
     */
    public function getDistinctCountries()
    {
        return $this->addresses()
            ->with('country')
            ->get()
            ->pluck('country')
            ->unique('code')
            ->values(); // Réindexe la collection
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
    public function getCountryName(?string $locale = null): ?string
    {
        // Code pays non défini
        if (empty($this->shipping_country)) {
            return null;
        }

        try {
            // Détecter la langue : paramètre, sinon celle de locale
            $locale = $locale ?? app()->getLocale();

            // Récupère la bonne liste
            $countryList = collect(config("countries.$locale"));

            // Retourne le nom traduit, ou le code s’il est absent
            return $countryList->get($this->shipping_country, $this->shipping_country);
        } catch (\Exception $e) {
            return $this->shipping_country;
        }
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

        try {
            // Utilisez le pays de l'utilisateur s'il est disponible, sinon par défaut 'FR'
            $country = $this->country_code ?? 'bf';

            return PhoneNumber::make($this->phone, $country)->formatInternational();
        } catch (\Exception $e) {
            // Fallback en cas d'erreur
            return $this->phone;
        }
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
            'email' => [
                'required',
                'email:rfc,dns',
                'max:160',
                Rule::unique('users', 'email')->ignore($id)
            ],
            'password' => [
                $id ? 'nullable' : 'required',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
            ],
            'phone' => 'nullable|string|max:30|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'profile_photo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Messages de validation personnalisés
     */

   /* public static function validationMessages()
    {
        return [
            // Nom
            'name.required' => 'Le nom est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 120 caractères.',

            // Email
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'email.max' => 'L\'adresse email ne peut pas dépasser 160 caractères.',

            // Mot de passe
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.regex' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.',

            // Téléphone
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 30 caractères.',
            'phone.regex' => 'Le format du numéro de téléphone n\'est pas valide.',

            // Photo de profil
            'profile_photo_path.image' => 'Le fichier doit être une image.',
            'profile_photo_path.mimes' => 'L\'image doit être au format jpeg, png, jpg ou gif.',
            'profile_photo_path.max' => 'L\'image ne peut pas dépasser 2 MB.',

            // Messages génériques utiles
            'required' => 'Ce champ est obligatoire.',
            'string' => 'Ce champ doit être une chaîne de caractères.',
            'max' => 'Ce champ ne peut pas dépasser :max caractères.',
        ];
    }*/
}
