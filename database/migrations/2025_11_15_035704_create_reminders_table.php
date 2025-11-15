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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_log_id')->constrained()->onDelete('cascade');
            $table->date('scheduled_date'); // Rejalashtirilgan eslatma sanasi
            $table->timestamp('sent_at')->nullable(); // Yuborilgan vaqt
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->enum('notification_type', ['sms', 'telegram', 'both'])->default('sms');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
