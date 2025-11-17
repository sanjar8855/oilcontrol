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
        Schema::create('service_log_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->integer('quantity')->default(1); // Ishlatilgan miqdor
            $table->decimal('unit_price', 12, 2); // O'sha paytdagi narx
            $table->decimal('total_price', 12, 2); // Umumiy (quantity * unit_price)

            $table->timestamps();

            $table->unique(['service_log_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_log_product');
    }
};
