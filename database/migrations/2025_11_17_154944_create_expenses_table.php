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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();

            $table->string('category'); // Elektr, Ish haqi, Ijara, Transport, Boshqa
            $table->string('title'); // Xarajat nomi
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2); // Summa

            $table->date('expense_date'); // Xarajat sanasi
            $table->string('payment_method')->nullable(); // Naqd, Bank, Click, Payme
            $table->string('receipt_number')->nullable(); // Kvitansiya raqami
            $table->string('attachment')->nullable(); // Fayl (chek rasmi)

            $table->timestamps();

            $table->index(['workshop_id', 'expense_date']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
