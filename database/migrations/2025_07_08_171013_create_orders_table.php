<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des commandes
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique de la commande');

            // Références Jetstream
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Client associé (si connecté)');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete()->comment('Équipe associée (pour les comptes business)');

            // Informations de commande
            $table->string('order_number', 30)->unique()->comment('Numéro de commande unique');
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded'
            ])->default('pending')->index()->comment('Statut de la commande');

            // Totaux financiers
            $table->decimal('subtotal', 12, 2)->comment('Sous-total produits');
            $table->decimal('shipping', 12, 2)->comment('Frais de livraison');
            $table->decimal('tax', 12, 2)->comment('Montant taxes');
            $table->decimal('total', 12, 2)->comment('Total à payer');
            $table->string('currency', 3)->default('EUR')->comment('Devise (ISO 4217)');

            // Informations client (pour les commandes sans compte)
            $table->string('guest_email')->nullable()->comment('Email client (commande sans compte)');
            $table->string('guest_name')->nullable()->comment('Nom client (commande sans compte)');

            // Paiement
            $table->string('payment_method', 50)->nullable()->comment('Méthode de paiement');
            $table->string('payment_id', 100)->nullable()->unique()->comment('ID transaction paiement');
            $table->timestamp('paid_at')->nullable()->comment('Date de paiement');

            // Adresses
            $table->json('billing_address')->nullable()->comment('Adresse de facturation');
            $table->json('shipping_address')->nullable()->comment('Adresse de livraison');

            // Métadonnées
            $table->text('notes')->nullable()->comment('Notes internes');
            $table->json('custom_fields')->nullable()->comment('Champs personnalisés');

            $table->timestamps();
            $table->softDeletes()->comment('Archivage des commandes');

            // Index pour les recherches
            $table->index('order_number');
            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });

        // Table des articles commandés
        Schema::create('order_items', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique de la ligne de commande');

            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->comment('Commande associée');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete()->comment('Produit associé');

            // Snapshot du produit au moment de la commande
            $table->string('product_name', 150)->comment('Nom du produit au moment de la commande');
            $table->string('sku', 50)->comment('SKU au moment de la commande');
            $table->decimal('unit_price', 12, 2)->comment('Prix unitaire');

            // Quantité et calculs
            $table->integer('quantity')->comment('Quantité commandée');
            $table->decimal('total_price', 12, 2)->comment('Prix total (unit_price * quantity)');

            // Options
            $table->json('options')->nullable()->comment('Options/variantes du produit');

            $table->timestamps();

            // Index
            $table->index('order_id');
            $table->index('sku');
        });

        // Table des historiques de statut
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique de l\'historique');

            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->comment('Commande associée');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Utilisateur ayant modifié le statut');

            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded'
            ])->comment('Statut à ce moment');

            $table->text('notes')->nullable()->comment('Commentaire sur le changement');
            $table->boolean('notify_customer')->default(false)->comment('Notification envoyée au client?');

            $table->timestamps();

            // Index pour l'analyse
            $table->index(['order_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
