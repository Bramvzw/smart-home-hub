<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lighting_scenes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('accent', 7);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lighting_scene_lights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lighting_scene_id')->constrained('lighting_scenes')->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('light_id', 128);
            $table->boolean('power');
            $table->unsignedTinyInteger('brightness')->nullable();
            $table->enum('mode', ['white', 'colour']);
            $table->string('color', 7)->nullable();
            $table->unsignedTinyInteger('color_temp')->nullable();

            $table->unique(['lighting_scene_id', 'provider', 'light_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lighting_scene_lights');
        Schema::dropIfExists('lighting_scenes');
    }
};
