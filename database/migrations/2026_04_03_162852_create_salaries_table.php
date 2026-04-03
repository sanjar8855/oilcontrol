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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workshop_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');

            // Oylik maosh to'lovi
            $table->decimal('amount', 12, 2)->comment('To\'langan summa');
            $table->string('month')->comment('Oy (YYYY-MM)');
            $table->date('payment_date')->comment('To\'lov sanasi');

            // To'lov usuli
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'other'])->default('cash');

            // Bonus va chegirmalar
            $table->decimal('bonus', 12, 2)->default(0)->comment('Bonus');
            $table->decimal('deduction', 12, 2)->default(0)->comment('Ushlab qolish/Jarimalar');

            // Izoh
            $table->text('notes')->nullable();

            // Kim to'ladi
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Index
            $table->index(['user_id', 'month']);
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
