<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->integer('avg_monthly_km')->nullable()->after('vin');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('default_avg_monthly_km');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('avg_monthly_km');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->integer('default_avg_monthly_km')->nullable()->after('phone');
        });
    }
};
