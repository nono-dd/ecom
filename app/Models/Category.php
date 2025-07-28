<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'depth',
        'path',
        'description',
        'image_url',
        'meta_title',
        'meta_description',
        'position',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'depth' => 'integer',
        'position' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * Boot method pour les événements du modèle
     */
    protected static function boot()
    {
        parent::boot();

        // Générer automatiquement le slug avant la création
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }

            // Calculer la profondeur et le chemin
            $category->calculateDepthAndPath();
        });

        // Mettre à jour la hiérarchie avant la mise à jour
        static::updating(function ($category) {
            // Si le parent a changé, recalculer la hiérarchie
            if ($category->isDirty('parent_id')) {
                $category->calculateDepthAndPath();
            }

            // Régénérer le slug si le nom a changé
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
        });

        // Mettre à jour les enfants après modification
        static::updated(function ($category) {
            if ($category->wasChanged(['parent_id', 'path'])) {
                $category->updateChildrenHierarchy();
            }
        });
    }

    // ========== MUTATEURS ==========

    /**
     * Mutateur pour le nom : première lettre en majuscule
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucfirst(trim($value));
    }

    /**
     * Mutateur pour le slug : format URL
     */
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = Str::slug($value);
    }

    /**
     * Mutateur pour le meta_title : limite à 70 caractères
     */
    public function setMetaTitleAttribute($value)
    {
        $this->attributes['meta_title'] = $value ? Str::limit($value, 70, '') : null;
    }

    /**
     * Mutateur pour le meta_description : limite à 160 caractères
     */
    public function setMetaDescriptionAttribute($value)
    {
        $this->attributes['meta_description'] = $value ? Str::limit($value, 160, '') : null;
    }

    // ========== ACCESSEURS ==========

    /**
     * Accesseur pour l'URL complète de l'image
     */
    public function getImageUrlAttribute($value)
    {
        if (empty($value)) {
            return $this->getDefaultImageUrl();
        }

        // Si c'est déjà une URL complète
        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        // Sinon, ajouter le domaine
        return asset('storage/' . $value);
    }

    /**
     * Accesseur pour le chemin complet avec noms
     */
    public function getFullPathAttribute()
    {
        if (empty($this->path)) {
            return $this->name;
        }

        $ids = explode('/', $this->path);
        $categories = self::whereIn('id', $ids)->pluck('name', 'id');

        $names = [];
        foreach ($ids as $id) {
            if (isset($categories[$id])) {
                $names[] = $categories[$id];
            }
        }

        $names[] = $this->name;
        return implode(' > ', $names);
    }

    /**
     * Accesseur pour l'URL SEO complète
     */
    public function getUrlAttribute()
    {
        return route('categories.show', $this->slug);
    }

    /**
     * Accesseur pour le breadcrumb
     */
    public function getBreadcrumbAttribute()
    {
        $breadcrumb = [];

        if ($this->path) {
            $ids = explode('/', $this->path);
            $parents = self::whereIn('id', $ids)->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')->get();

            foreach ($parents as $parent) {
                $breadcrumb[] = [
                    'name' => $parent->name,
                    'url' => $parent->url,
                    'slug' => $parent->slug
                ];
            }
        }

        $breadcrumb[] = [
            'name' => $this->name,
            'url' => $this->url,
            'slug' => $this->slug
        ];

        return $breadcrumb;
    }

    // ========== RELATIONS ==========

    /**
     * Relation : catégorie parente
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relation : catégories enfants directs
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
                   ->where('is_active', true)
                   ->orderBy('position');
    }

    /**
     * Relation : tous les descendants
     */
    public function descendants()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('descendants');
    }

    /**
     * Relation : tous les ancêtres
     */
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors->reverse();
    }

    /**
     * Relation avec les produits (exemple)
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // ========== SCOPES ==========

    /**
     * Scope pour les catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les catégories racines (sans parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope pour les catégories de premier niveau
     */
    public function scopeTopLevel($query)
    {
        return $query->where('depth', 0);
    }

    /**
     * Scope pour chercher par nom
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', '%' . $term . '%')
                    ->orWhere('description', 'like', '%' . $term . '%');
    }

    /**
     * Scope pour ordonner par position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('name');
    }

    /**
     * Scope pour les catégories avec enfants
     */
    public function scopeWithChildren($query)
    {
        return $query->has('children');
    }

    /**
     * Scope pour les catégories feuilles (sans enfants)
     */
    public function scopeLeaves($query)
    {
        return $query->doesntHave('children');
    }

    // ========== MÉTHODES MÉTIER ==========

    /**
     * Vérifier si la catégorie est racine
     */
    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    /**
     * Vérifier si la catégorie est une feuille
     */
    public function isLeaf()
    {
        return $this->children()->count() === 0;
    }

    /**
     * Vérifier si c'est un descendant d'une autre catégorie
     */
    public function isDescendantOf($category)
    {
        if ($this->path) {
            return Str::contains($this->path, (string) $category->id);
        }

        return $this->parent_id === $category->id;
    }

    /**
     * Vérifier si c'est un ancêtre d'une autre catégorie
     */
    public function isAncestorOf($category)
    {
        return $category->isDescendantOf($this);
    }

    /**
     * Obtenir tous les enfants récursivement
     */
    public function getAllDescendants()
    {
        return self::where('path', 'like', '%/' . $this->id . '/%')
                  ->orWhere('parent_id', $this->id)
                  ->get();
    }

    /**
     * Compter le nombre total de produits (incluant les sous-catégories)
     */
    public function getTotalProductsCount()
    {
        $count = $this->products()->count();

        foreach ($this->getAllDescendants() as $descendant) {
            $count += $descendant->products()->count();
        }

        return $count;
    }

    // ========== MÉTHODES PRIVÉES ==========

    /**
     * Calculer la profondeur et le chemin hiérarchique
     */
    private function calculateDepthAndPath()
    {
        if (is_null($this->parent_id)) {
            $this->depth = 0;
            $this->path = null;
        } else {
            $parent = self::find($this->parent_id);

            if ($parent) {
                $this->depth = $parent->depth + 1;
                $this->path = $parent->path ? $parent->path . '/' . $parent->id : (string) $parent->id;
            }
        }
    }

    /**
     * Mettre à jour la hiérarchie des enfants
     */
    private function updateChildrenHierarchy()
    {
        $children = $this->children()->get();

        foreach ($children as $child) {
            $child->calculateDepthAndPath();
            $child->save();

            // Récursif pour tous les descendants
            $child->updateChildrenHierarchy();
        }
    }

    /**
     * Générer un slug unique
     */
    private function generateUniqueSlug($name, $id = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->when($id, function ($query, $id) {
            return $query->where('id', '!=', $id);
        })->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Obtenir l'URL de l'image par défaut
     */
    private function getDefaultImageUrl()
    {
        return 'https://via.placeholder.com/300x200/e2e8f0/64748b?text=' . urlencode($this->name);
    }

    // ========== MÉTHODES STATIQUES ==========

    /**
     * Construire l'arbre complet des catégories
     */
    public static function getTree($parentId = null, $activeOnly = true)
    {
        $query = self::where('parent_id', $parentId)->ordered();

        if ($activeOnly) {
            $query->active();
        }

        $categories = $query->get();

        foreach ($categories as $category) {
            $category->children_tree = self::getTree($category->id, $activeOnly);
        }

        return $categories;
    }

    /**
     * Obtenir le menu hiérarchique
     */
    public static function getMenuTree()
    {
        return self::roots()->active()->ordered()->with(['children' => function ($query) {
            $query->active()->ordered();
        }])->get();
    }

    // ========== VALIDATION ==========

    /**
     * Règles de validation
     */
    public static function validationRules($id = null)
    {
        return [
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:110|unique:categories,slug,' . $id,
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:2048',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'position' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    public static function validationMessages()
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire',
            'name.max' => 'Le nom ne peut pas dépasser 100 caractères',
            'slug.unique' => 'Ce slug est déjà utilisé',
            'parent_id.exists' => 'La catégorie parente n\'existe pas',
            'meta_title.max' => 'Le titre SEO ne peut pas dépasser 70 caractères',
            'meta_description.max' => 'La description SEO ne peut pas dépasser 160 caractères',
            'image_url.url' => 'L\'URL de l\'image n\'est pas valide',
        ];
    }
}
