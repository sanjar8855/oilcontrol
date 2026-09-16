<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_products', function (Blueprint $table) {
            // Mahsulot turi — filtrlash va avtomatik tanlov uchun asosiy kalit
            $table->string('product_type', 40)->nullable()->after('unit');

            // Texnik xarakteristikalar
            $table->string('viscosity', 20)->nullable()->after('product_type');   // 5W-30
            $table->string('oil_base', 20)->nullable()->after('viscosity');       // synthetic|semi_synthetic|mineral
            $table->string('api_spec', 40)->nullable()->after('oil_base');        // SP, CI-4/SL, GL-5
            $table->decimal('volume_liters', 8, 3)->nullable()->after('api_spec');
            $table->unsignedSmallInteger('pack_qty')->nullable()->after('volume_liters');

            // Yetkazib beruvchi va manba
            $table->string('supplier_code', 60)->nullable()->after('pack_qty');
            $table->string('source', 120)->nullable()->after('supplier_code');
            $table->string('image_url')->nullable()->after('source');

            // Tavsiya etilgan narx (workshop mahsulot yaratganda default bo'lib tortiladi)
            $table->decimal('recommended_price', 12, 2)->nullable()->after('image_url');
            $table->char('currency', 3)->default('USD')->after('recommended_price');
            $table->timestamp('price_updated_at')->nullable()->after('currency');

            $table->index('product_type');
            $table->index('viscosity');
            $table->index('supplier_code');
        });

        // sku — seeder uchun idempotent kalit
        Schema::table('global_products', function (Blueprint $table) {
            $table->unique('sku');
        });
    }

    public function down(): void
    {
        Schema::table('global_products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropIndex(['product_type']);
            $table->dropIndex(['viscosity']);
            $table->dropIndex(['supplier_code']);
            $table->dropColumn([
                'product_type', 'viscosity', 'oil_base', 'api_spec', 'volume_liters',
                'pack_qty', 'supplier_code', 'source', 'image_url',
                'recommended_price', 'currency', 'price_updated_at',
            ]);
        });
    }
};
