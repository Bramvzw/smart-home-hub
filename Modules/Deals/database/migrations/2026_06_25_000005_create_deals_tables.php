<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watched_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('query');
            $table->string('category')->nullable();
            $table->string('image_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('product_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watched_product_id')->constrained('watched_products')->cascadeOnDelete();
            $table->string('retailer', 32);
            $table->string('external_id')->nullable();
            $table->string('title');
            $table->string('url');
            $table->decimal('current_price', 10, 2)->nullable();
            $table->decimal('lowest_price', 10, 2)->nullable();
            $table->boolean('confirmed')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['watched_product_id', 'retailer', 'external_id']);
            $table->index(['confirmed', 'active']);
        });

        Schema::create('price_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_listing_id')->constrained('product_listings')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->timestamp('observed_at');
            $table->timestamps();

            $table->index(['product_listing_id', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_points');
        Schema::dropIfExists('product_listings');
        Schema::dropIfExists('watched_products');
    }
};
