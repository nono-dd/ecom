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
            $table->morphs('addressable'); // addressable_id et addressable_type
            $table->enum('type', ['home', 'work', 'billing', 'shipping'])->default('home');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code');
            $table->string('country_code', 3); // Code ISO du pays
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('country_code')->references('code')->on('countries');
            $table->index(['addressable_type', 'addressable_id']);
            $table->index(['addressable_type', 'addressable_id', 'is_default']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
}
