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
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamp('added_at');
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });
/*
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique de l\'article dans la liste');

            // Références
            $table->foreignId('wishlist_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Liste associée');

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Produit ajouté');

            // Métadonnées
            $table->text('notes')->nullable()->comment('Notes personnelles');
            $table->integer('priority')->default(0)->comment('Priorité (0-100)');

            // Notifications
            $table->boolean('notify_on_price_drop')->default(false)->comment('Alerte si baisse de prix');
            $table->boolean('notify_on_restock')->default(false)->comment('Alerte si retour en stock');

            $table->timestamps();

            // Index
            $table->index('wishlist_id');
            $table->index('product_id');
            $table->index('created_at');
        });*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
