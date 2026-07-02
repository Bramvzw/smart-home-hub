<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('music_releases', function (Blueprint $table) {
            $table->boolean('saved_on_spotify')->nullable()->after('notified');
        });
    }

    public function down(): void
    {
        Schema::table('music_releases', function (Blueprint $table) {
            $table->dropColumn('saved_on_spotify');
        });
    }
};
