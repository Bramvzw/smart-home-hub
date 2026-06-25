<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('briefings', function (Blueprint $table): void {
            $table->id();
            $table->date('date')->unique();
            $table->text('body');
            $table->json('sections');
            $table->timestamp('generated_at');
            $table->string('model')->nullable();
            $table->boolean('is_fallback')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('briefings');
    }
};
