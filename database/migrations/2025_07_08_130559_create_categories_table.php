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
        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('Identifiant unique de la catégorie');


            $table->string('name', 100)->index()->comment('Nom de la catégorie');
            $table->string('slug', 110)->unique()->comment('Slug pour les URLs SEO');

            // Hiérarchie (arbre de catégories)
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Catégorie parente');
            $table->integer('depth')->default(0)->comment('Profondeur dans l\'arborescence');
            $table->string('path', 255)->nullable()->comment('Chemin hiérarchique complet');

            // Métadonnées
            $table->text('description')->nullable()->comment('Description de la catégorie');
            $table->string('image_url', 2048)->nullable()->comment('Image de la catégorie');

            // SEO
            $table->string('meta_title', 70)->nullable()->comment('Titre SEO (max 70 caractères)');
            $table->string('meta_description', 160)->nullable()->comment('Description SEO (max 160 caractères)');

            // Organisation
            $table->integer('position')->default(0)->index()->comment('Ordre d\'affichage');
            $table->boolean('is_active')->default(true)->index()->comment('Statut actif/inactif');

            $table->timestamps();
            $table->softDeletes()->comment('Date de suppression douce');

            // Contrainte pour la hiérarchie
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
