<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unify "recurring goal": planner intentions become plannable habits. Existing
 * planner_intentions are migrated into task_recurrences (owned by Tasks); the
 * planner links plan items to the habit via recurrence_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Migrate existing intentions into plannable habits (best-effort; both tables must exist).
        if (Schema::hasTable('planner_intentions') && Schema::hasTable('task_recurrences')) {
            $now = CarbonImmutable::now()->toDateTimeString();

            foreach (DB::table('planner_intentions')->get() as $intention) {
                $min = max(1, (int) $intention->target_min);
                $max = max($min, (int) $intention->target_max);
                $windows = json_decode($intention->preferred_windows ?? '[]', true);

                DB::table('task_recurrences')->insert([
                    'board_id' => null,
                    'type' => 'habit',
                    'title' => $intention->title,
                    'description' => null,
                    'cadence_type' => $intention->frequency_type === 'weekly' ? 'weekly' : 'times_per_week',
                    'cadence_config' => json_encode([
                        'times' => $min,
                        'target_min' => $min,
                        'target_max' => $max,
                        'category' => $intention->category,
                        'preferred_windows' => is_array($windows) ? $windows : [],
                    ]),
                    'notify' => true,
                    'active' => (bool) $intention->active,
                    'plannable' => true,
                    'duration_minutes' => $intention->duration_minutes,
                    'next_due_on' => null,
                    'last_materialized_on' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 2. Repoint plan items at habits; plans regenerate, so clear existing rows.
        if (Schema::hasTable('planner_plan_items')) {
            DB::table('planner_plan_items')->delete();

            Schema::table('planner_plan_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('intention_id');
            });

            Schema::table('planner_plan_items', function (Blueprint $table) {
                $table->unsignedBigInteger('recurrence_id')->nullable()->after('plan_id');
                $table->string('category')->nullable()->after('title');
            });
        }

        if (Schema::hasTable('planner_plans')) {
            DB::table('planner_plans')->delete();
        }

        // 3. Intentions now live as habits.
        Schema::dropIfExists('planner_intentions');
    }

    public function down(): void
    {
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

        if (Schema::hasTable('planner_plan_items')) {
            Schema::table('planner_plan_items', function (Blueprint $table) {
                $table->dropColumn(['recurrence_id', 'category']);
                $table->foreignId('intention_id')->nullable()->after('plan_id')
                    ->constrained('planner_intentions')->nullOnDelete();
            });
        }
    }
};
