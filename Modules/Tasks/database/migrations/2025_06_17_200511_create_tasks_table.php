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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lane_id')->constrained('lanes')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('label')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('notify_before_expiry')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
