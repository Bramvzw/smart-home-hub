<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grocery_offers', function (Blueprint $table) {
            $table->id();
            $table->string('store', 20);
            $table->string('external_id')->nullable();
            $table->string('product_name');
            $table->string('category')->nullable();
            $table->decimal('normal_price', 8, 2)->nullable();
            $table->decimal('offer_price', 8, 2)->nullable();
            $table->string('discount_label')->nullable();
            $table->string('unit')->nullable();
            $table->string('image_url')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->string('week_key');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['week_key', 'store']);
            $table->unique(['store', 'external_id', 'week_key']);
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('week_key');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('servings')->default(2);
            $table->unsignedInteger('time_minutes')->nullable();
            $table->decimal('estimated_cost', 8, 2)->nullable();
            $table->json('ingredients');
            $table->json('steps');
            $table->json('shopping_list');
            $table->string('model')->nullable();
            $table->boolean('is_fallback')->default(false);
            $table->timestamps();

            $table->index('week_key');
        });

        Schema::create('recipe_runs', function (Blueprint $table) {
            $table->id();
            $table->string('week_key')->unique();
            $table->json('stores_fetched')->nullable();
            $table->json('stores_failed')->nullable();
            $table->boolean('ai_unavailable')->default(false);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_runs');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('grocery_offers');
    }
};
