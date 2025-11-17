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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Moylar, Filtrlar, Ehtiyot qismlar
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Icon nomi (optional)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('workshop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
