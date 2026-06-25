<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filament_spools', function (Blueprint $table) {
            $table->id();
            $table->string('material');
            $table->string('color_name');
            $table->string('color_hex')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('diameter_mm', 3, 2)->default(1.75);
            $table->unsignedInteger('total_weight_g')->default(1000);
            $table->unsignedInteger('remaining_g');
            $table->decimal('purchase_price', 8, 2)->nullable();
            $table->string('purchase_store')->nullable();
            $table->date('purchased_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('printer_parts', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['spare', 'consumable']);
            $table->string('name');
            $table->decimal('quantity', 8, 2);
            $table->string('unit')->nullable();
            $table->decimal('purchase_price', 8, 2)->nullable();
            $table->string('purchase_store')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_parts');
        Schema::dropIfExists('filament_spools');
    }
};
