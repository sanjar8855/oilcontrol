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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // 10W-40 Moy, Havo filtri
            $table->string('sku')->nullable(); // SKU/Artikul raqami
            $table->text('description')->nullable();
            $table->string('unit')->default('dona'); // dona, litr, kg

            // Narxlar
            $table->decimal('purchase_price', 12, 2)->default(0); // Sotib olish narxi
            $table->decimal('selling_price', 12, 2)->default(0); // Sotish narxi

            // Ombor
            $table->integer('stock_quantity')->default(0); // Joriy qoldiq
            $table->integer('min_stock_level')->default(0); // Minimal qoldiq (ogohlantirish uchun)

            // Boshqa
            $table->string('barcode')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('track_inventory')->default(true); // Omborda kuzatilsinmi?

            $table->timestamps();

            $table->index(['workshop_id', 'category_id']);
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
