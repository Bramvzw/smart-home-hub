<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_recurrences', function (Blueprint $table) {
            // A plannable habit is also scheduled into free time by the weekly planner.
            $table->boolean('plannable')->default(false)->after('active');
            $table->unsignedInteger('duration_minutes')->nullable()->after('plannable');

            $table->index(['plannable', 'active']);
        });
    }

    public function down(): void
    {
        Schema::table('task_recurrences', function (Blueprint $table) {
            $table->dropIndex(['plannable', 'active']);
            $table->dropColumn(['plannable', 'duration_minutes']);
        });
    }
};
