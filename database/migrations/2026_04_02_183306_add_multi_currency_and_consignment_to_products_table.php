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
        Schema::table('products', function (Blueprint $table) {
            // Valyuta turi (USD yoki UZS)
            $table->enum('currency', ['USD', 'UZS'])->default('UZS')->after('category_id');

            // Olish narxlari
            $table->decimal('purchase_price_usd', 10, 2)->nullable()->after('currency');
            $table->decimal('purchase_price_uzs', 12, 2)->nullable()->after('purchase_price_usd');

            // Sotuv narxlari
            $table->decimal('selling_price_usd', 10, 2)->nullable()->after('purchase_price_uzs');
            $table->decimal('selling_price_uzs', 12, 2)->nullable()->after('selling_price_usd');

            // Realizatsiya ma'lumotlari
            $table->boolean('is_consignment')->default(false)->after('selling_price_uzs')->comment('Realizatsiyami?');
            $table->decimal('consignment_percentage', 5, 2)->nullable()->after('is_consignment')->comment('Realizatsiya %');
            $table->string('supplier')->nullable()->after('consignment_percentage')->comment('Diler/Ta\'minotchi');

            // Exchange rate (agar kerak bo'lsa)
            $table->decimal('exchange_rate', 10, 2)->nullable()->after('supplier')->comment('$ kursi');

            // Eski ustunlarni nullable qilamiz (yangi ustunlar ishlatiladi)
            $table->decimal('purchase_price', 10, 2)->nullable()->change();
            $table->decimal('selling_price', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'purchase_price_usd',
                'purchase_price_uzs',
                'selling_price_usd',
                'selling_price_uzs',
                'is_consignment',
                'consignment_percentage',
                'supplier',
                'exchange_rate',
            ]);
        });
    }
};
