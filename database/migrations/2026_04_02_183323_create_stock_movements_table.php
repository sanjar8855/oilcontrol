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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('workshop_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');

            // Harakat turi: in (kirim), out (chiqim), adjustment (tuzatish)
            $table->enum('movement_type', ['in', 'out', 'adjustment'])->default('in');

            // Miqdor
            $table->decimal('quantity', 10, 2);
            $table->decimal('remaining_quantity', 10, 2)->comment('Qolgan miqdor (FIFO uchun)');

            // Birlik narxi (ikki valyutada)
            $table->decimal('unit_cost_usd', 10, 2)->nullable();
            $table->decimal('unit_cost_uzs', 12, 2)->nullable();
            $table->enum('currency', ['USD', 'UZS'])->default('UZS');

            // Umumiy summa
            $table->decimal('total_cost', 12, 2)->nullable();

            // Reference (qaysi operatsiyaga tegishli)
            $table->string('reference_type')->nullable()->comment('ServiceLog, Purchase, etc.');
            $table->unsignedBigInteger('reference_id')->nullable();

            // Izoh
            $table->text('notes')->nullable();

            // Foydalanuvchi
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();

            // Index
            $table->index(['product_id', 'movement_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
