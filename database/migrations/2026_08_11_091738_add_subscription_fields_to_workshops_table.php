<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_plan');
            $table->json('limits_override')->nullable()->after('subscription_expires_at');
        });

        // subscription_plan endi config/plans.php orqali boshqariladi (enum emas, erkin string).
        // sqlite (test muhiti) MODIFY'ni tushunmaydi va enum'ni CHECK constraint sifatida saqlaydi,
        // shuning uchun u yerda ustunni butunlay qayta yaratamiz (doctrine/dbal talab qilinmasin uchun).
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('workshops', function (Blueprint $table) {
                $table->dropColumn('subscription_plan');
            });
            Schema::table('workshops', function (Blueprint $table) {
                $table->string('subscription_plan', 20)->default('trial')->after('address');
            });
        } else {
            DB::statement("ALTER TABLE workshops MODIFY subscription_plan VARCHAR(20) NOT NULL DEFAULT 'trial'");
        }

        DB::table('workshops')->where('subscription_plan', 'free')->update(['subscription_plan' => 'trial']);
        DB::table('workshops')->where('subscription_plan', 'business')->update(['subscription_plan' => 'maxsus']);

        // Mavjud kompaniyalarga (obuna sanasi hali belgilanmagan bo'lsa) 30 kunlik sinov muddati beriladi,
        // shunda yangi CheckSubscription middleware ularni birdaniga bloklab qo'ymaydi.
        DB::table('workshops')
            ->whereNull('subscription_expires_at')
            ->update(['trial_ends_at' => now()->addDays(30)]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('workshops')->where('subscription_plan', 'trial')->update(['subscription_plan' => 'free']);
        DB::table('workshops')->where('subscription_plan', 'maxsus')->update(['subscription_plan' => 'business']);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('workshops', function (Blueprint $table) {
                $table->dropColumn('subscription_plan');
            });
            Schema::table('workshops', function (Blueprint $table) {
                $table->string('subscription_plan', 20)->default('free')->after('address');
            });
        } else {
            DB::statement("ALTER TABLE workshops MODIFY subscription_plan ENUM('free','start','pro','business') NOT NULL DEFAULT 'free'");
        }

        Schema::table('workshops', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'limits_override']);
        });
    }
};
