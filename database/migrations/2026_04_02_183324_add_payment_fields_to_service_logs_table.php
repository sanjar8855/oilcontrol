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
        Schema::table('service_logs', function (Blueprint $table) {
            // To'lov turi: cash (naqd), credit (nasiya), installment (bo'lib to'lash)
            $table->enum('payment_type', ['cash', 'credit', 'installment'])->default('cash')->after('cost');

            // To'lov holati: paid (to'langan), partial (qisman), unpaid (to'lanmagan)
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid')->after('payment_type');

            // To'langan summa
            $table->decimal('paid_amount', 12, 2)->default(0)->after('payment_status');

            // Qolgan summa
            $table->decimal('remaining_amount', 12, 2)->default(0)->after('paid_amount');

            // To'lash muddati (nasiya uchun)
            $table->date('due_date')->nullable()->after('remaining_amount');

            // Realizatsiya ma'lumotlari
            $table->boolean('is_consignment')->default(false)->after('due_date')->comment('Realizatsiyami?');
            $table->decimal('consignment_percentage', 5, 2)->nullable()->after('is_consignment');

            // Discount
            $table->decimal('discount_amount', 10, 2)->default(0)->after('consignment_percentage');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_amount');

            // Umumiy summa (mahsulotlar + ish haqi - chegirma)
            $table->decimal('total_amount', 12, 2)->nullable()->after('discount_percentage');

            // Valyuta
            $table->enum('currency', ['USD', 'UZS'])->default('UZS')->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_logs', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'payment_status',
                'paid_amount',
                'remaining_amount',
                'due_date',
                'is_consignment',
                'consignment_percentage',
                'discount_amount',
                'discount_percentage',
                'total_amount',
                'currency',
            ]);
        });
    }
};
