<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierTransaction;

class SupplierLedgerService
{
    /**
     * Ta'minotchidan yuk/mahsulot kelganda qarzni oshirish (qarzga sotib olish).
     */
    public function recordPurchase(
        int $workshopId,
        int $supplierId,
        float $amount,
        string $currency = 'UZS',
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null
    ): SupplierTransaction {
        return SupplierTransaction::create([
            'workshop_id' => $workshopId,
            'supplier_id' => $supplierId,
            'type' => 'purchase',
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => $userId,
            'transaction_date' => now(),
        ]);
    }

    /**
     * Ta'minotchiga pul to'langanda qarzni kamaytirish. Naqd pul chiqimi
     * sifatida bir vaqtning o'zida Xarajatlar (Expenses) bo'limiga ham yozuv
     * qo'shiladi, shunda umumiy pul chiqimi hisobotlarda to'g'ri ko'rinadi.
     */
    public function recordPayment(
        int $workshopId,
        int $supplierId,
        float $amount,
        string $currency = 'UZS',
        ?string $paymentMethod = null,
        ?string $notes = null,
        ?int $userId = null
    ): SupplierTransaction {
        $supplier = Supplier::findOrFail($supplierId);

        $transaction = SupplierTransaction::create([
            'workshop_id' => $workshopId,
            'supplier_id' => $supplierId,
            'type' => 'payment',
            'amount' => $amount,
            'currency' => $currency,
            'description' => $notes,
            'payment_method' => $paymentMethod,
            'user_id' => $userId,
            'transaction_date' => now(),
        ]);

        $supplier->workshop->expenses()->create([
            'branch_id' => null,
            'category' => 'Ta\'minotchiga to\'lov',
            'title' => "To'lov: {$supplier->name}",
            'description' => $notes,
            'amount' => $amount,
            'expense_date' => now(),
            'payment_method' => $paymentMethod,
        ]);

        return $transaction;
    }
}
