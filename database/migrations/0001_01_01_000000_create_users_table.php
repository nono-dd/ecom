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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // l'identifiant unique
            $table->string('name', 120); // nom
            $table->string('email', 160)->unique(); // email il doit être unique
            $table->timestamp('email_verified_at')->nullable(); // verification de l'email
            $table->string('password'); // mot de passe
            $table->rememberToken(); // colonne est utilisée par Laravel pour stocker un jeton (token) sécurisé lorsque l'utilisateur coche la case "Se souvenir de moi"

            $table->string('shipping_country', 2)->nullable()->comment('Code pays ISO 3166-1 alpha-2'); // adresse du pays de livraison
            $table->string('shipping_city', 100)->nullable();  // adresse de la ville de livraison
            $table->string('shipping_postal_code', 23)->nullable();  // le code postal
            $table->text('billing_address')->nullable(); // adresse de facture
            $table->string('phone', 30)->nullable()->comment('Format international');; // Numéro de téléphone

            // indexation des champs courants pour une recherche rapide
            $table->index('shipping_country');
            $table->index('shipping_city');
            $table->index('shipping_postal_code');

            // $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps(); // stocke la date a laquelle on creer un user
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 160)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index()->constrained()
                ->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
