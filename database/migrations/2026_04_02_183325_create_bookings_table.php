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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('workshop_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');

            // Bron qilingan vaqt
            $table->date('booking_date');
            $table->time('booking_time');

            // Xizmat turi
            $table->string('service_type')->nullable()->comment('Moy almashtirish, to\'liq xizmat, etc.');

            // Holat: pending, confirmed, completed, cancelled
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');

            // Telegram ma'lumotlari
            $table->string('telegram_user_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('telegram_message_id')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_name')->nullable();

            // Izoh
            $table->text('notes')->nullable();

            // ServiceLog bilan bog'lanish (xizmat bajarilgandan keyin)
            $table->foreignId('service_log_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();

            // Index
            $table->index(['booking_date', 'status']);
            $table->index(['workshop_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
