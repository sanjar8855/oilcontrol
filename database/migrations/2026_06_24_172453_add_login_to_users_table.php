<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->nullable()->unique()->after('name');
        });

        // Mavjud foydalanuvchilar uchun login qiymatini email'dan to'ldirish
        DB::table('users')->whereNull('login')->orderBy('id')->get(['id', 'email'])->each(function ($user) {
            $login = $user->email ? Str::before($user->email, '@') : 'user'.$user->id;
            DB::table('users')->where('id', $user->id)->update(['login' => $login]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('login');
        });
    }
};
