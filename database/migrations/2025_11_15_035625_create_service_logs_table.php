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
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->date('service_date');
            $table->integer('odometer_reading'); // Hozirgi probeg (km)
            $table->integer('next_service_km')->default(5000); // Keyingi servis uchun km
            $table->integer('avg_monthly_km')->nullable(); // Oyiga o'rtacha km
            $table->string('service_type')->default('oil_change'); // Servis turi
            $table->decimal('cost', 10, 2)->nullable(); // Xizmat narxi
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};
