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
         // Table des historiques de statut
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique de l\'historique');

            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->comment('Commande associée');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Utilisateur ayant modifié le statut');

            $table->enum('status', [
                'pending', //en attente
                'confirmed', //  confirmé
                'processing', //  traitement
                'shipped', //  expédié
                'delivered', // livré
                'cancelled', // annulé
                'refunded' //  remboursé
            ])->comment('Statut à ce moment');

            $table->text('notes')->nullable()->comment('Commentaire sur le changement');
            $table->boolean('notify_customer')->default(false)->comment('Notification envoyée au client?');

            $table->timestamps();

            // Index pour l'analyse
            $table->index(['order_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
