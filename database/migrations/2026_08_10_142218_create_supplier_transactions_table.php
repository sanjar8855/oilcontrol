<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase', 'payment']);
            $table->decimal('amount', 14, 2);
            $table->enum('currency', ['USD', 'UZS'])->default('UZS');
            $table->string('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->timestamps();

            $table->index(['supplier_id', 'type']);
            $table->index('workshop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_transactions');
    }
};
