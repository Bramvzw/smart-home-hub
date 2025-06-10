<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('label');
            $table->string('priority')->nullable()->after('due_date');
            $table->json('urls')->nullable()->after('priority');
            $table->boolean('notify_before_expiry')->default(false)->after('urls');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('due_date');
            $table->dropColumn('priority');
            $table->dropColumn('urls');
            $table->dropColumn('notify_before_expiry');
        });
    }
};
