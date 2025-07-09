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
        Schema::create('carts', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique du panier');

            // Référence à l'utilisateur (peut être nul pour les paniers de visiteurs)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Utilisateur associé (si connecté)');

            // Session ID pour les paniers de visiteurs
            $table->string('session_id', 100)->nullable()->index()->comment('ID de session pour les invités');

            // Autres champs utiles
            $table->string('currency', 3)->default('EUR')->comment('Devise du panier');
            $table->decimal('total', 12, 2)->default(0.00)->comment('Total du panier');
            $table->decimal('subtotal', 12, 2)->default(0.00)->comment('Sous-total avant remise');
            $table->decimal('discount', 12, 2)->default(0.00)->comment('Montant de la remise');
            $table->decimal('tax', 12, 2)->default(0.00)->comment('Montant des taxes');

            // Coupon de réduction
            $table->string('coupon_code', 50)->nullable()->comment('Code de réduction appliqué');
            $table->decimal('coupon_discount', 12, 2)->default(0.00)->comment('Montant de la réduction du coupon');

            // Expiration (pour nettoyage automatique)
            $table->timestamp('expires_at')->nullable()->comment('Date d\'expiration du panier');

            $table->timestamps();

            // Index
            $table->index(['user_id', 'session_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
