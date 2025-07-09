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
            // $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete()->comment('Équipe associée (pour les comptes business)');

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

            // Références
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Commande associée');

            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Produit associé');

            // Snapshots du produit au moment de la commande
            $table->string('product_name', 150)->comment('Nom du produit au moment de la commande');
            $table->string('sku', 50)->comment('SKU au moment de la commande');
            $table->text('description')->nullable()->comment('Description au moment de la commande');

            // Prix et quantité
            $table->decimal('unit_price', 12, 2)->comment('Prix unitaire au moment de la commande');
            $table->integer('quantity')->comment('Quantité commandée');
            $table->decimal('total_price', 12, 2)->comment('Prix total (unit_price * quantity)');

            // Options et personnalisations
            $table->json('options')->nullable()->comment('Options/variantes du produit');
            $table->json('customizations')->nullable()->comment('Personnalisations spécifiques');

            // Taxes et remises
            $table->decimal('tax_rate', 5, 2)->default(0.00)->comment('Taux de taxe appliqué (%)');
            $table->decimal('discount_amount', 12, 2)->default(0.00)->comment('Montant de remise appliqué');

            // Intégration Laravel Cashier (pour les abonnements)
            $table->string('stripe_price_id')->nullable()->comment('ID Stripe du prix');
            $table->string('stripe_subscription_id')->nullable()->comment('ID Stripe de l\'abonnement');

            // Intégration Spatie Media Library
            $table->string('featured_image_url', 2048)->nullable()->comment('URL de l\'image principale');
            $table->json('media_urls')->nullable()->comment('URLs des médias associés');

            // Suivi individuel
            $table->timestamp('shipped_at')->nullable()->comment('Date d\'expédition de cet article');
            $table->timestamp('delivered_at')->nullable()->comment('Date de livraison de cet article');
            $table->string('tracking_number', 100)->nullable()->comment('Numéro de suivi spécifique');

            // Statut pour Filament
            $table->enum('status', [
                'pending',
                'confirmed',
                'shipped',
                'delivered',
                'returned',
                'refunded'
            ])->default('pending')->index()->comment('Statut individuel de l\'article');

            $table->timestamps();

            // Index optimisés
            $table->index('order_id');
            $table->index('sku');
            $table->index('product_id');
            $table->index('status');
            $table->index('created_at');
        });




        // Table des historiques de statut
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique de l\'historique');

            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->comment('Commande associée');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Utilisateur ayant modifié le statut');

            $table->enum('status', [
                'pending', //en attente
                'confirmed',//  confirmé
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

        public function down(): void
        {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        }

};
