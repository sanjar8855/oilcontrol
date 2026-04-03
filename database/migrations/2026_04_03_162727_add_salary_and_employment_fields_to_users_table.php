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
            // Oylik maosh
            $table->decimal('salary', 12, 2)->nullable()->after('branch_id')->comment('Oylik maosh');

            // Ish boshlash sanasi
            $table->date('hire_date')->nullable()->after('salary')->comment('Ishga qabul qilingan sana');

            // Lavozim/Pozitsiya
            $table->string('position')->nullable()->after('hire_date')->comment('Lavozim');

            // Ish holati: active, on_leave, terminated
            $table->enum('employment_status', ['active', 'on_leave', 'terminated'])->default('active')->after('position');

            // Qo'shimcha telefon
            $table->string('phone_secondary')->nullable()->after('phone')->comment('Qo\'shimcha telefon');

            // Manzil
            $table->text('address')->nullable()->after('employment_status')->comment('Uy manzili');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'salary',
                'hire_date',
                'position',
                'employment_status',
                'phone_secondary',
                'address',
            ]);
        });
    }
};
