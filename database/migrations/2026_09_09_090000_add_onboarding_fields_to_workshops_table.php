<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->string('onboarding_step')->nullable()->after('is_active');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
        });

        // Mavjud workshoplar hech qachon onboarding ko'rmasligi kerak.
        DB::table('workshops')
            ->whereNull('onboarding_step')
            ->whereNull('onboarding_completed_at')
            ->update(['onboarding_completed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropColumn(['onboarding_step', 'onboarding_completed_at']);
        });
    }
};
