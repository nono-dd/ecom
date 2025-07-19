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
            $table->string('marque');

            $table->decimal('price', 10, 2)->comment('Le prix du produit');
            $table->decimal('cost_price', 10, 2)->nullable()->comment('Prix de revient');

            $table->integer('stock')->default(0)->comment('La quantite de stock dans la boutique');
            $table->string('sku', 50)->unique()->comment('La référence unique du produit');
            $table->string('barcode', 30)->nullable()->comment('Code-barres');

            $table->boolean('is_active')->default(true)->comment('Statut actif/inactif');
            $table->timestamp('published_at')->nullable()->comment('Date de publication');
            $table->timestamps();

            $table->softDeletes()->comment('Date de suppression douce'); /* je ne mentirais pas en disant que cette ligne m'a été donné par DEEPSEEK*/

            $table->index('name');
            $table->index('is_active');

            $table->index(['price', 'stock']);
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
