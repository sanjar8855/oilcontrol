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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 20);
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['cash', 'p2p', 'click', 'payme'])->default('cash');
            $table->string('provider_transaction_id')->nullable();
            $table->json('provider_payload')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'failed'])->default('confirmed');
            $table->unsignedInteger('period_days')->default(30);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
