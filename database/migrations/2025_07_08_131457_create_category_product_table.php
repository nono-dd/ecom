<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            // Clés étrangères avec contraintes
            $table->foreignId('categories_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Référence à la catégorie');

            $table->foreignId('products_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Référence au produit');

            // Clé primaire composite
            $table->primary(['categories_id', 'products_id'], 'category_product_primary');

            // Index supplémentaires optimisés
            $table->index('categories_id', 'category_product_category_index');
            $table->index('products_id', 'category_product_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
