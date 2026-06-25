<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_items', function (Blueprint $table): void {
            $table->id();
            $table->string('feed_key');
            $table->string('topic')->index();
            $table->string('guid');
            $table->string('title');
            $table->string('url', 2048);
            $table->text('summary');
            $table->string('author')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->timestamp('published_at')->index();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->boolean('notified')->default(false);
            $table->json('matched_keywords')->nullable();
            $table->timestamps();

            $table->unique(['feed_key', 'guid']);
            $table->index(['topic', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};
