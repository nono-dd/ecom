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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique de l\'avis');

            // Références
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Produit évalué');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Utilisateur ayant posté l\'avis');

            $table->foreignId('order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Commande associée (si achat vérifié)');

            // Contenu de l'avis
            $table->string('title', 200)->comment('Titre de l\'avis');
            $table->text('content')->comment('Contenu détaillé');
            $table->integer('rating')->unsigned()->between(1, 5)->comment('Note de 1 à 5 étoiles');

            // Métadonnées
            $table->boolean('is_verified')->default(false)->comment('Achat vérifié');
            $table->boolean('is_featured')->default(false)->comment('Avis mis en avant');
            $table->boolean('is_published')->default(true)->comment('Statut de publication');

            // Modération
            $table->timestamp('moderated_at')->nullable()->comment('Date de modération');
            $table->foreignId('moderator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Modérateur ayant approuvé');

            $table->timestamps();
            $table->softDeletes()->comment('Suppression douce');

            // Index
            $table->index(['product_id', 'is_published']);
            $table->index(['user_id', 'created_at']);
            $table->index('rating');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
