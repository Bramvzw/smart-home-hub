<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_tokens', function (Blueprint $table) {
            $table->id();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('planner_intentions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('category', ['sport', 'family', 'date', 'custom']);
            $table->enum('frequency_type', ['times_per_week', 'weekly']);
            $table->unsignedTinyInteger('target_min')->default(1);
            $table->unsignedTinyInteger('target_max')->default(1);
            $table->json('preferred_windows')->nullable();
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('location')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('planner_plans', function (Blueprint $table) {
            $table->id();
            $table->string('week_key')->unique();
            $table->text('summary')->nullable();
            $table->enum('status', ['proposed', 'partly_accepted', 'accepted'])->default('proposed');
            $table->boolean('is_fallback')->default(false);
            $table->timestamp('generated_at');
            $table->timestamps();
        });

        Schema::create('planner_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('planner_plans')->cascadeOnDelete();
            $table->foreignId('intention_id')->nullable()->constrained('planner_intentions')->nullOnDelete();
            $table->string('title');
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->enum('status', ['proposed', 'accepted', 'rejected', 'unplaceable'])->default('proposed');
            $table->string('unplaceable_reason')->nullable();
            $table->string('google_event_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_plan_items');
        Schema::dropIfExists('planner_plans');
        Schema::dropIfExists('planner_intentions');
        Schema::dropIfExists('google_calendar_tokens');
    }
};
