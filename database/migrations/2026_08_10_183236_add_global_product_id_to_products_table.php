<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('global_product_id')->nullable()->after('workshop_id')
                ->constrained()->nullOnDelete();

            // Bitta workshop bitta katalog mahsulotidan faqat bir marta nusxa ola oladi
            $table->unique(['workshop_id', 'global_product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['workshop_id', 'global_product_id']);
            $table->dropConstrainedForeignId('global_product_id');
        });
    }
};
