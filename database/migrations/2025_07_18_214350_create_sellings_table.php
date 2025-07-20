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
        Schema::create('sellings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(1);
            $table->enum('status', ['active', 'sold', 'inactive', 'pending'])->default('active');
            $table->text('description')->nullable();
            $table->enum('condition', ['new', 'like_new', 'good', 'fair', 'poor']);
            $table->timestamp('listed_at');
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selling');
    }
};
