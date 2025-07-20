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
            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Référence à la catégorie');

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Référence au produit');

            // Clé primaire composite
            $table->primary(['category_id', 'product_id'], 'category_product_primary');

            // Index supplémentaires optimisés
            $table->index('category_id', 'category_product_category_index');
            $table->index('product_id', 'category_product_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
