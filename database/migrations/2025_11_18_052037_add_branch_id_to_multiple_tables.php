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
        // Clients jadvaliga branch_id qo'shish
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('workshop_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });

        // Products jadvaliga branch_id qo'shish
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('workshop_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });

        // Expenses jadvaliga branch_id qo'shish
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('workshop_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });

        // Inventory transactions jadvaliga branch_id qo'shish
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('workshop_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });

        // Service logs jadvaliga branch_id qo'shish
        Schema::table('service_logs', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('service_logs', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};
