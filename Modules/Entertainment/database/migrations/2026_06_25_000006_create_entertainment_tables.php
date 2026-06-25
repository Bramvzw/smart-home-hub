<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taste_profiles', function (Blueprint $table) {
            $table->id();
            $table->json('favorite_titles')->nullable();
            $table->json('genres')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('film_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tmdb_id');
            $table->string('title');
            $table->enum('sentiment', ['up', 'down']);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('film_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tmdb_id')->unique();
            $table->string('title');
            $table->text('overview')->nullable();
            $table->json('availability');
            $table->string('poster_url')->nullable();
            $table->text('why')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->boolean('dismissed')->default(false);
            $table->timestamp('refreshed_at');
            $table->timestamps();
        });

        Schema::create('concerts', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32);
            $table->string('external_id')->nullable();
            $table->string('artist');
            $table->string('title')->nullable();
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->dateTime('date');
            $table->string('url')->nullable();
            $table->enum('relevance', ['followed', 'hedon', 'might_like', 'none'])->default('none');
            $table->boolean('notified')->default(false);
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index(['relevance', 'notified']);
        });

        Schema::create('music_releases', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_id')->unique();
            $table->string('artist');
            $table->string('title');
            $table->enum('type', ['album', 'single', 'ep']);
            $table->date('release_date');
            $table->string('url')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('notified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('music_releases');
        Schema::dropIfExists('concerts');
        Schema::dropIfExists('film_recommendations');
        Schema::dropIfExists('film_feedback');
        Schema::dropIfExists('taste_profiles');
    }
};
