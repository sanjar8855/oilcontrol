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
        Schema::table('car_models', function (Blueprint $table) {
            $table->decimal('oil_capacity_liters', 4, 2)->nullable()->after('name');
            $table->decimal('antifreeze_capacity_min_liters', 4, 2)->nullable()->after('oil_capacity_liters');
            $table->decimal('antifreeze_capacity_max_liters', 4, 2)->nullable()->after('antifreeze_capacity_min_liters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_models', function (Blueprint $table) {
            $table->dropColumn(['oil_capacity_liters', 'antifreeze_capacity_min_liters', 'antifreeze_capacity_max_liters']);
        });
    }
};
