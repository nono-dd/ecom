<?php

namespace App\Http\Validation;

class ValidationMessages
{
    public static function user()
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
        ];
    }

    public static function address()
    {
        return [
            // Type d'adresse
            'type.required' => 'Le type d\'adresse est obligatoire.',
            'type.in' => 'Le type d\'adresse doit être : livraison, facturation, domicile ou travail.',

            // Rue
            'street.required' => 'L\'adresse est obligatoire.',
            'street.string' => 'L\'adresse doit être une chaîne de caractères.',
            'street.max' => 'L\'adresse ne peut pas dépasser 255 caractères.',

            // Ville
            'city.required' => 'La ville est obligatoire.',
            'city.string' => 'La ville doit être une chaîne de caractères.',
            'city.max' => 'La ville ne peut pas dépasser 100 caractères.',

            // Code postal
            'postal_code.required' => 'Le code postal est obligatoire.',
            'postal_code.string' => 'Le code postal doit être une chaîne de caractères.',
            'postal_code.max' => 'Le code postal ne peut pas dépasser 23 caractères.',

            // Pays
            'country.required' => 'Le pays est obligatoire.',
            'country.size' => 'Le code pays doit contenir exactement 2 caractères.',
            'country.exists' => 'Ce pays n\'existe pas dans notre base de données.',

            // Utilisateur
            'user_id.required' => 'L\'utilisateur est obligatoire.',
            'user_id.exists' => 'Cet utilisateur n\'existe pas.',

            // Défaut
            'is_default.boolean' => 'Le champ adresse par défaut doit être vrai ou faux.',
        ];
    }

    public static function product()
    {
        return [
            // Nom du produit
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.string' => 'Le nom du produit doit être une chaîne de caractères.',
            'name.max' => 'Le nom du produit ne peut pas dépasser 255 caractères.',

            // Description
            'description.string' => 'La description doit être une chaîne de caractères.',

            // Prix
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix doit être positif.',

            // Stock
            'stock.required' => 'Le stock est obligatoire.',
            'stock.integer' => 'Le stock doit être un nombre entier.',
            'stock.min' => 'Le stock ne peut pas être négatif.',

            // SKU
            'sku.unique' => 'Cette référence produit (SKU) est déjà utilisée.',
            'sku.string' => 'La référence produit (SKU) doit être une chaîne de caractères.',
            'sku.max' => 'La référence produit (SKU) ne peut pas dépasser 100 caractères.',

            // Statut
            'status.in' => 'Le statut doit être : actif, inactif ou brouillon.',

            // Image
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format jpeg, png, jpg ou gif.',
            'image.max' => 'L\'image ne peut pas dépasser 5 MB.',
        ];
    }

    public static function category()
    {
        return [
            // Nom
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.string' => 'Le nom de la catégorie doit être une chaîne de caractères.',
            'name.max' => 'Le nom de la catégorie ne peut pas dépasser 100 caractères.',
            'name.unique' => 'Cette catégorie existe déjà.',

            // Slug
            'slug.unique' => 'Ce slug de catégorie est déjà utilisé.',
            'slug.regex' => 'Le slug ne peut contenir que des lettres, chiffres et tirets.',

            // Description
            'description.string' => 'La description doit être une chaîne de caractères.',

            // Parent
            'parent_id.exists' => 'La catégorie parent n\'existe pas.',

            // Status
            'is_active.boolean' => 'Le statut actif doit être vrai ou faux.',
        ];
    }

    public static function order()
    {
        return [
            // Statut
            'status.required' => 'Le statut de la commande est obligatoire.',
            'status.in' => 'Le statut doit être : en attente, confirmée, expédiée, livrée ou annulée.',

            // Total
            'total.required' => 'Le montant total est obligatoire.',
            'total.numeric' => 'Le montant total doit être un nombre.',
            'total.min' => 'Le montant total doit être positif.',

            // Adresse de livraison
            'shipping_address_id.required' => 'L\'adresse de livraison est obligatoire.',
            'shipping_address_id.exists' => 'Cette adresse de livraison n\'existe pas.',

            // Adresse de facturation
            'billing_address_id.exists' => 'Cette adresse de facturation n\'existe pas.',

            // Utilisateur
            'user_id.required' => 'L\'utilisateur est obligatoire.',
            'user_id.exists' => 'Cet utilisateur n\'existe pas.',

            // Notes
            'notes.string' => 'Les notes doivent être une chaîne de caractères.',
        ];
    }

    public static function review()
    {
        return [
            // Note
            'rating.required' => 'La note est obligatoire.',
            'rating.integer' => 'La note doit être un nombre entier.',
            'rating.min' => 'La note minimum est 1.',
            'rating.max' => 'La note maximum est 5.',

            // Commentaire
            'comment.string' => 'Le commentaire doit être une chaîne de caractères.',
            'comment.max' => 'Le commentaire ne peut pas dépasser 1000 caractères.',

            // Produit
            'product_id.required' => 'Le produit est obligatoire.',
            'product_id.exists' => 'Ce produit n\'existe pas.',

            // Utilisateur
            'user_id.required' => 'L\'utilisateur est obligatoire.',
            'user_id.exists' => 'Cet utilisateur n\'existe pas.',
        ];
    }

    public static function country()
    {
        return [
            // Code
            'code.required' => 'Le code pays est obligatoire.',
            'code.size' => 'Le code pays doit contenir exactement 2 caractères.',
            'code.unique' => 'Ce code pays existe déjà.',
            'code.alpha' => 'Le code pays ne peut contenir que des lettres.',

            // Nom
            'name.required' => 'Le nom du pays est obligatoire.',
            'name.string' => 'Le nom du pays doit être une chaîne de caractères.',
            'name.max' => 'Le nom du pays ne peut pas dépasser 100 caractères.',

            // Code téléphone
            'phone_code.required' => 'L\'indicatif téléphonique est obligatoire.',
            'phone_code.string' => 'L\'indicatif téléphonique doit être une chaîne de caractères.',
            'phone_code.max' => 'L\'indicatif téléphonique ne peut pas dépasser 5 caractères.',
        ];
    }

    public static function cart()
    {
        return [
            // Utilisateur
            'user_id.required' => 'L\'utilisateur est obligatoire.',
            'user_id.exists' => 'Cet utilisateur n\'existe pas.',

            // Session
            'session_id.string' => 'L\'identifiant de session doit être une chaîne de caractères.',
        ];
    }

    public static function cartItem()
    {
        return [
            // Panier
            'cart_id.required' => 'Le panier est obligatoire.',
            'cart_id.exists' => 'Ce panier n\'existe pas.',

            // Produit
            'product_id.required' => 'Le produit est obligatoire.',
            'product_id.exists' => 'Ce produit n\'existe pas.',

            // Quantité
            'quantity.required' => 'La quantité est obligatoire.',
            'quantity.integer' => 'La quantité doit être un nombre entier.',
            'quantity.min' => 'La quantité minimum est 1.',
            'quantity.max' => 'La quantité maximum est 99.',

            // Prix
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix doit être positif.',
        ];
    }

    public static function wishlist()
    {
        return [
            // Nom
            'name.string' => 'Le nom de la liste doit être une chaîne de caractères.',
            'name.max' => 'Le nom de la liste ne peut pas dépasser 100 caractères.',

            // Utilisateur
            'user_id.required' => 'L\'utilisateur est obligatoire.',
            'user_id.exists' => 'Cet utilisateur n\'existe pas.',

            // Produit
            'product_id.required' => 'Le produit est obligatoire.',
            'product_id.exists' => 'Ce produit n\'existe pas.',
        ];
    }

    public static function selling()
    {
        return [
            // Titre
            'title.required' => 'Le titre de l\'annonce est obligatoire.',
            'title.string' => 'Le titre doit être une chaîne de caractères.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',

            // Description
            'description.required' => 'La description est obligatoire.',
            'description.string' => 'La description doit être une chaîne de caractères.',

            // Prix
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric' => 'Le prix doit être un nombre.',
            'price.min' => 'Le prix doit être positif.',

            // Condition
            'condition.required' => 'L\'état du produit est obligatoire.',
            'condition.in' => 'L\'état doit être : neuf, très bon état, bon état ou usagé.',

            // Statut
            'status.in' => 'Le statut doit être : actif, vendu ou retiré.',

            // Utilisateur
            'user_id.required' => 'Le vendeur est obligatoire.',
            'user_id.exists' => 'Ce vendeur n\'existe pas.',

            // Catégorie
            'category_id.exists' => 'Cette catégorie n\'existe pas.',
        ];
    }

    // Messages génériques réutilisables
    public static function generic()
    {
        return [
            'required' => 'Ce champ est obligatoire.',
            'string' => 'Ce champ doit être une chaîne de caractères.',
            'email' => 'Veuillez saisir une adresse email valide.',
            'unique' => 'Cette valeur est déjà utilisée.',
            'exists' => 'Cette valeur n\'existe pas.',
            'numeric' => 'Ce champ doit être un nombre.',
            'integer' => 'Ce champ doit être un nombre entier.',
            'boolean' => 'Ce champ doit être vrai ou faux.',
            'min' => 'Ce champ doit contenir au minimum :min caractères.',
            'max' => 'Ce champ ne peut pas dépasser :max caractères.',
            'size' => 'Ce champ doit contenir exactement :size caractères.',
            'in' => 'La valeur sélectionnée n\'est pas valide.',
            'image' => 'Le fichier doit être une image.',
            'mimes' => 'Le fichier doit être au format :values.',
        ];
    }

    // Méthode pour fusionner les messages
    public static function merge(...$messageArrays)
    {
        return array_merge(...$messageArrays);
    }
}
