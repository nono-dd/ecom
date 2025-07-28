<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique du produit');
            $table->string('name', 150)->comment('Nom du produit');
            $table->string('slug', 160)->unique()->comment('Slug pour les URLs SEO');
            $table->text('description')->nullable()->comment('Description du produit');
            $table->text('short_description')->nullable()->comment('Description courte pour les listings');
            $table->string('brand', 100)->comment('Marque du produit'); // Renommé en anglais

            // Prix et coûts
            $table->decimal('price', 10, 2)->comment('Prix de vente du produit');
            $table->decimal('cost_price', 10, 2)->nullable()->comment('Prix de revient');
            $table->decimal('sale_price', 10, 2)->nullable()->comment('Prix en promotion');
            $table->timestamp('sale_starts_at')->nullable()->comment('Début de la promotion');
            $table->timestamp('sale_ends_at')->nullable()->comment('Fin de la promotion');

            // Stock et références
            $table->integer('stock')->default(0)->comment('Quantité en stock');
            $table->integer('min_stock')->default(0)->comment('Stock minimum (alerte)');
            $table->string('sku', 50)->unique()->comment('Référence unique du produit');
            $table->string('barcode', 30)->nullable()->unique()->comment('Code-barres');

            // Dimensions et poids (utiles pour la livraison)
            $table->decimal('weight', 8, 2)->nullable()->comment('Poids en kg');
            $table->decimal('length', 8, 2)->nullable()->comment('Longueur en cm');
            $table->decimal('width', 8, 2)->nullable()->comment('Largeur en cm');
            $table->decimal('height', 8, 2)->nullable()->comment('Hauteur en cm');

            // SEO et métadonnées
            $table->string('meta_title', 160)->nullable()->comment('Titre SEO');
            $table->string('meta_description', 255)->nullable()->comment('Description SEO');

            // Statuts
            $table->boolean('is_active')->default(true)->comment('Statut actif/inactif');
            $table->boolean('is_featured')->default(false)->comment('Produit mis en avant');
            $table->boolean('track_inventory')->default(true)->comment('Suivre le stock');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->comment('Statut de publication');

            // Dates
            $table->timestamp('published_at')->nullable()->comment('Date de publication');
            $table->timestamps();
            $table->softDeletes()->comment('Date de suppression douce');

            // Index pour les performances
            $table->index('name');
            $table->index('slug');
            $table->index('brand');
            $table->index('sku');
            $table->index(['is_active', 'status']);
            $table->index('is_featured');
            $table->index(['price', 'stock']);
            $table->index('published_at');
            $table->index(['sale_starts_at', 'sale_ends_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
