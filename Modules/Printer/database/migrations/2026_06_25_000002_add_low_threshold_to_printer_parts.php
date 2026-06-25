<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printer_parts', function (Blueprint $table) {
            $table->unsignedInteger('low_threshold')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('printer_parts', function (Blueprint $table) {
            $table->dropColumn('low_threshold');
        });
    }
};
