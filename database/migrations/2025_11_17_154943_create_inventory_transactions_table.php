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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_log_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['in', 'out', 'adjustment']); // kirim, chiqim, tuzatish
            $table->integer('quantity'); // Miqdor (musbat yoki manfiy)
            $table->integer('quantity_before')->default(0); // Oldingi qoldiq
            $table->integer('quantity_after')->default(0); // Keyingi qoldiq

            $table->decimal('unit_price', 12, 2)->nullable(); // Birlik narxi
            $table->decimal('total_price', 12, 2)->nullable(); // Umumiy narx

            $table->string('reason')->nullable(); // Sabab (Sotib olish, Servisda ishlatildi, Tuzatish)
            $table->text('notes')->nullable();
            $table->date('transaction_date')->default(DB::raw('CURRENT_DATE'));

            $table->timestamps();

            $table->index(['product_id', 'type']);
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
