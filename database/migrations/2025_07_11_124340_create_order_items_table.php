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
                'shipped', //  expédié
                'delivered',
                'cancelled',
                'refunded' //  remboursé
            ])->default('pending')->index()->comment('Statut individuel de l\'article');

            $table->timestamps();

            // Index optimisés
            $table->index('order_id');
            $table->index('sku');
            // $table->index('product_id'); index déjà dispo
            // $table->index('status');  idem
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // $table->dropForeign(['product_id']);
            // $table->dropIndex(['status']);
        });

        Schema::dropIfExists('order_items');
    }
};
