<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_products', function (Blueprint $table) {
            // OEM / analog kod: GM 96985730 ↔ WIX WL7491 ↔ Carville CRL6021
            $table->string('oem_code', 60)->nullable()->after('supplier_code');

            // Mos avtomobillar — VAQTINCHA matnli maslahat maydoni.
            // To'liq yechim: car_model_global_products (many-to-many) jadvali.
            // Bu ustun faqat qidiruv/ko'rsatish uchun, biznes-logika unga tayanmasin.
            $table->string('fits_models', 255)->nullable()->after('oem_code');

            $table->index('oem_code');
        });
    }

    public function down(): void
    {
        Schema::table('global_products', function (Blueprint $table) {
            $table->dropIndex(['oem_code']);
            $table->dropColumn(['oem_code', 'fits_models']);
        });
    }
};
