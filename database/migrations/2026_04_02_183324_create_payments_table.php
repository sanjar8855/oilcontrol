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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_log_id')->constrained()->onDelete('cascade');
            $table->foreignId('workshop_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');

            // To'lov ma'lumotlari
            $table->decimal('amount', 12, 2);
            $table->enum('currency', ['USD', 'UZS'])->default('UZS');
            $table->date('payment_date');

            // To'lov usuli: cash, card, transfer, other
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'other'])->default('cash');

            // Izoh
            $table->text('notes')->nullable();

            // Foydalanuvchi
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();

            // Index
            $table->index(['service_log_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
