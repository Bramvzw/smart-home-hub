<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->nullable()->constrained('task_boards')->nullOnDelete();
            $table->enum('type', ['habit', 'maintenance']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('cadence_type', ['times_per_week', 'weekdays', 'weekly', 'monthly', 'interval', 'annual']);
            $table->json('cadence_config')->nullable();
            $table->boolean('notify')->default(true);
            $table->boolean('active')->default(true);
            $table->date('next_due_on')->nullable();
            $table->date('last_materialized_on')->nullable();
            $table->timestamps();

            $table->index(['type', 'active']);
            $table->index(['active', 'next_due_on']);
        });

        Schema::create('task_recurrence_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurrence_id')->constrained('task_recurrences')->cascadeOnDelete();
            $table->date('completed_on');
            $table->string('period_key');
            $table->timestamps();

            $table->unique(['recurrence_id', 'period_key']);
        });

        Schema::table('kanban_tasks', function (Blueprint $table) {
            $table->foreignId('recurrence_id')
                ->nullable()
                ->after('column_id')
                ->constrained('task_recurrences')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kanban_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurrence_id');
        });

        Schema::dropIfExists('task_recurrence_completions');
        Schema::dropIfExists('task_recurrences');
    }
};
