<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_log_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
            
            $table->unique(['service_log_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_log_product');
    }
};
