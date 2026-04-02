<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    /**
     * Mahsulot kirimini qayd qilish (sotib olish, qabul qilish)
     */
    public function recordIncoming(
        int $productId,
        float $quantity,
        float $unitCostUsd = null,
        float $unitCostUzs = null,
        string $currency = 'UZS',
        string $referenceType = null,
        int $referenceId = null,
        string $notes = null
    ): StockMovement {
        $product = Product::findOrFail($productId);

        // Total cost hisoblash
        $unitCost = $currency === 'USD' ? $unitCostUsd : $unitCostUzs;
        $totalCost = $unitCost * $quantity;

        // Stock movement yaratish
        $movement = StockMovement::create([
            'product_id' => $productId,
            'workshop_id' => $product->workshop_id,
            'branch_id' => $product->branch_id,
            'movement_type' => 'in',
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_cost_usd' => $unitCostUsd,
            'unit_cost_uzs' => $unitCostUzs,
            'currency' => $currency,
            'total_cost' => $totalCost,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        // Mahsulot stock miqdorini yangilash
        $product->increment('stock_quantity', $quantity);

        return $movement;
    }

    /**
     * Mahsulot chiqimini qayd qilish (FIFO usulida)
     */
    public function recordOutgoing(
        int $productId,
        float $quantity,
        string $referenceType = null,
        int $referenceId = null,
        string $notes = null
    ): array {
        $product = Product::findOrFail($productId);

        if ($product->stock_quantity < $quantity) {
            throw new \Exception("Mahsulot yetarli emas. Mavjud: {$product->stock_quantity}, Kerak: {$quantity}");
        }

        $remainingToConsume = $quantity;
        $totalCost = 0;
        $consumedMovements = [];

        // FIFO: eng eski kirimlardan boshlab chiqarish
        $incomingMovements = StockMovement::where('product_id', $productId)
            ->where('movement_type', 'in')
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        DB::beginTransaction();

        try {
            foreach ($incomingMovements as $movement) {
                if ($remainingToConsume <= 0) {
                    break;
                }

                $consumeQuantity = min($remainingToConsume, $movement->remaining_quantity);

                // Bu kirimdan narxni olish
                $unitCost = $movement->currency === 'USD'
                    ? $movement->unit_cost_usd
                    : $movement->unit_cost_uzs;

                $cost = $unitCost * $consumeQuantity;
                $totalCost += $cost;

                // Kirim harakatining qolgan miqdorini kamaytirish
                $movement->remaining_quantity -= $consumeQuantity;
                $movement->save();

                $consumedMovements[] = [
                    'movement_id' => $movement->id,
                    'quantity' => $consumeQuantity,
                    'unit_cost' => $unitCost,
                    'cost' => $cost,
                    'currency' => $movement->currency,
                ];

                $remainingToConsume -= $consumeQuantity;
            }

            // Chiqim harakatini yaratish
            $outgoingMovement = StockMovement::create([
                'product_id' => $productId,
                'workshop_id' => $product->workshop_id,
                'branch_id' => $product->branch_id,
                'movement_type' => 'out',
                'quantity' => $quantity,
                'remaining_quantity' => 0, // Chiqim uchun har doim 0
                'unit_cost_usd' => null,
                'unit_cost_uzs' => null,
                'currency' => $product->currency,
                'total_cost' => $totalCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);

            // Mahsulot stock miqdorini kamaytirish
            $product->decrement('stock_quantity', $quantity);

            DB::commit();

            return [
                'movement' => $outgoingMovement,
                'total_cost' => $totalCost,
                'average_cost' => $quantity > 0 ? $totalCost / $quantity : 0,
                'consumed_movements' => $consumedMovements,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mahsulot stock tuzatish
     */
    public function recordAdjustment(
        int $productId,
        float $newQuantity,
        string $notes = null
    ): StockMovement {
        $product = Product::findOrFail($productId);

        $difference = $newQuantity - $product->stock_quantity;
        $movementType = $difference > 0 ? 'in' : 'out';
        $quantity = abs($difference);

        $movement = StockMovement::create([
            'product_id' => $productId,
            'workshop_id' => $product->workshop_id,
            'branch_id' => $product->branch_id,
            'movement_type' => 'adjustment',
            'quantity' => $quantity,
            'remaining_quantity' => $movementType === 'in' ? $quantity : 0,
            'unit_cost_usd' => $product->purchase_price_usd,
            'unit_cost_uzs' => $product->purchase_price_uzs,
            'currency' => $product->currency,
            'total_cost' => null,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => $notes ?? "Stock tuzatish: {$product->stock_quantity} -> {$newQuantity}",
            'user_id' => auth()->id(),
        ]);

        // Mahsulot stock miqdorini yangilash
        $product->stock_quantity = $newQuantity;
        $product->save();

        return $movement;
    }

    /**
     * Mahsulot uchun o'rtacha narxni hisoblash
     */
    public function getAverageCost(int $productId): float
    {
        $product = Product::findOrFail($productId);

        $movements = StockMovement::where('product_id', $productId)
            ->where('movement_type', 'in')
            ->where('remaining_quantity', '>', 0)
            ->get();

        if ($movements->isEmpty()) {
            return $product->getPurchasePrice();
        }

        $totalCost = 0;
        $totalQuantity = 0;

        foreach ($movements as $movement) {
            $cost = $movement->currency === 'USD'
                ? $movement->unit_cost_usd
                : $movement->unit_cost_uzs;

            $totalCost += $cost * $movement->remaining_quantity;
            $totalQuantity += $movement->remaining_quantity;
        }

        return $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;
    }

    /**
     * Mahsulot inventarizatsiya ma'lumotlari
     */
    public function getInventoryDetails(int $productId): array
    {
        $product = Product::findOrFail($productId);

        $movements = StockMovement::where('product_id', $productId)
            ->where('movement_type', 'in')
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $batches = [];
        $totalValue = 0;

        foreach ($movements as $movement) {
            $unitCost = $movement->currency === 'USD'
                ? $movement->unit_cost_usd
                : $movement->unit_cost_uzs;

            $batchValue = $unitCost * $movement->remaining_quantity;
            $totalValue += $batchValue;

            $batches[] = [
                'date' => $movement->created_at,
                'quantity' => $movement->remaining_quantity,
                'unit_cost' => $unitCost,
                'currency' => $movement->currency,
                'batch_value' => $batchValue,
                'reference' => $movement->reference_type,
            ];
        }

        return [
            'product' => $product,
            'total_stock' => $product->stock_quantity,
            'batches' => $batches,
            'total_value' => $totalValue,
            'average_cost' => $this->getAverageCost($productId),
        ];
    }
}
