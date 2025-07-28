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
        // Créer la table countries
        Schema::create('countries', function (Blueprint $table) {
            $table->char('code', 2)->primary(); // Code ISO 3166-1 alpha-2 (FR, US, etc.)
            $table->string('name', 100);
            $table->string('phone_code', 5);
            $table->char('iso3', 3)->nullable(); // Code ISO 3166-1 alpha-3 (FRA, USA, etc.)
            $table->string('currency_code', 3)->nullable(); // Code devise (EUR, USD, etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index pour améliorer les performances de recherche
            $table->index('name');
            $table->index('is_active');
        });

        // (Seulement si la table addresses existe déjà)
        if (Schema::hasTable('addresses')) {
            Schema::table('addresses', function (Blueprint $table) {
                // Vérifier si la colonne country_code existe
                if (!Schema::hasColumn('addresses', 'country_code')) {
                    $table->char('country_code', 3)->after('postal_code');
                }

                // Ajouter la contrainte de clé étrangère
                $table->foreign('country_code')
                    ->references('code')
                    ->on('countries')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer d'abord la contrainte de clé étrangère si elle existe
        if (Schema::hasTable('addresses')) {
            Schema::table('addresses', function (Blueprint $table) {
                $table->dropForeign(['country_code']);
            });
        }

        // Supprimer la table countries
        Schema::dropIfExists('countries');
    }
};
