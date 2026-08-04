<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite (testlarda ishlatiladi) haqiqiy ENUM turini bilmaydi va uni
        // yaratilish vaqtidagi CHECK constraint orqali emulyatsiya qiladi —
        // shu sabab bu MySQL'ga xos ALTER faqat mysql drayverida bajariladi.
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'card', 'click', 'transfer', 'other') NOT NULL DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE payments SET payment_method = 'other' WHERE payment_method = 'click'");
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'card', 'transfer', 'other') NOT NULL DEFAULT 'cash'");
    }
};
