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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique de l\'article du panier');

            // Référence au panier
            $table->foreignId('cart_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Panier associé');

            // Référence au produit
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Produit associé');

            // Snapshot du produit (au cas où il change)
            $table->string('product_name', 150)->comment('Nom du produit au moment de l\'ajout');
            $table->string('sku', 50)->comment('SKU du produit au moment de l\'ajout');
            $table->decimal('price', 12, 2)->comment('Prix unitaire au moment de l\'ajout');

            // Quantité et calcul
            $table->integer('quantity')->default(1)->comment('Quantité');
            $table->decimal('total', 12, 2)->comment('Prix total (price * quantity)');

            // Options et personnalisations
            $table->json('options')->nullable()->comment('Options/variantes du produit');
            $table->json('customizations')->nullable()->comment('Personnalisations spécifiques');

            // Intégration Spatie Media
            $table->string('featured_image_url', 2048)->nullable()->comment('URL de l\'image principale');

            $table->timestamps();

            // Index
            $table->index('cart_id');
            $table->index('product_id');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
