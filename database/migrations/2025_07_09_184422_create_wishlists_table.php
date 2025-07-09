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
            $table->id()->comment('Identifiant unique de la liste');

            // Référence à l'utilisateur
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Propriétaire de la liste');

            // Configuration
            $table->string('name', 100)->comment('Nom de la liste');
            $table->string('slug', 110)->unique()->comment('Slug pour le partage');
            $table->boolean('is_public')->default(false)->comment('Liste publique ou privée');
            $table->boolean('is_default')->default(false)->comment('Liste par défaut de l\'utilisateur');

            // Statistiques
            $table->integer('items_count')->default(0)->comment('Nombre d\'articles');
            $table->timestamp('last_added_at')->nullable()->comment('Dernier ajout');

            $table->timestamps();

            // Index
            $table->index(['user_id', 'is_default']);
            $table->index('is_public');
        });

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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
