<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Lien avec users
            $table->enum('type', ['shipping', 'billing'])->default('shipping'); // shipping ou billing
            $table->string('country',2)->comment('ISO 3166-1 alpha-2 country code');
            $table->string('city', 100);
            $table->string('postal_code', 22);
            $table->string('street_address', 255)->nullable();
            $table->string('phone', 30)->nullable()->comment('Format: +[code pays][numéro]');


            $table->index(['user_id', 'type']);
            $table->index('country');
            $table->index('postal_code');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
}
