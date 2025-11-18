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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['superadmin', 'director', 'manager', 'employee'])->default('employee')->after('email_verified_at');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete()->after('role');

            $table->index('role');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn(['role', 'branch_id']);
        });
    }
};
