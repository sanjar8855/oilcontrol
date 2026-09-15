<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('telegram_verified_at')->nullable()->after('telegram_chat_id');
        });

        // Mavjud userlar bloklanmasin — faqat yangi ro'yxatdan o'tuvchilar
        // Telegram orqali tasdiqlashi shart.
        DB::table('users')->whereNull('telegram_verified_at')->update(['telegram_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_verified_at');
        });
    }
};
