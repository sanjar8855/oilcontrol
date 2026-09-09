<?php

namespace App\Services;

use App\Models\GlobalProduct;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Support\Str;

/**
 * Mahsulot yaratishning umumiy yo'li — ProductController (qo'lda qo'shish,
 * ko'p qo'shish, katalogdan nusxalash) va OnboardingController (1-qadam)
 * o'rtasida umumiy: boshlang'ich qoldiq StockMovementService orqali qayd
 * qilinadi, xarid narxi ta'minotchiga qarz yoki Xarajat sifatida yoziladi,
 * mahsulot esa GlobalProductMatcher orqali global katalog bilan
 * moslashtiriladi/bog'lanadi.
 */
class ProductCreationService
{
    public function __construct(private GlobalProductMatcher $matcher)
    {
    }

    public function createWithInitialStock(Workshop $workshop, User $user, array $validated): Product
    {
        $product = $workshop->products()->create($validated);

        if (is_null($product->global_product_id)) {
            $this->linkToGlobalCatalog($product, $workshop);
        }

        if ($product->stock_quantity > 0 && $product->track_inventory) {
            $stockService = new StockMovementService();

            $stockService->recordIncoming(
                productId: $product->id,
                quantity: $product->stock_quantity,
                unitCostUsd: $product->purchase_price_usd,
                unitCostUzs: $product->purchase_price_uzs,
                currency: $product->currency,
                referenceType: 'InitialStock',
                referenceId: null,
                notes: "Boshlang'ich qoldiq"
            );

            InventoryTransaction::create([
                'workshop_id' => $workshop->id,
                'branch_id' => $product->branch_id,
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $product->stock_quantity,
                'quantity_before' => 0,
                'quantity_after' => $product->stock_quantity,
                'unit_price' => $product->getPurchasePrice(),
                'total_price' => $product->stock_quantity * $product->getPurchasePrice(),
                'reason' => 'Boshlang\'ich qoldiq',
                'transaction_date' => now(),
            ]);

            $totalCost = $product->stock_quantity * $product->getPurchasePrice();

            if ($product->supplier_id) {
                (new SupplierLedgerService())->recordPurchase(
                    workshopId: $workshop->id,
                    supplierId: $product->supplier_id,
                    amount: $totalCost,
                    currency: $product->currency,
                    description: "Mahsulot sotib olish: {$product->name} ({$product->stock_quantity} {$product->unit})",
                    referenceType: 'Product',
                    referenceId: $product->id,
                    userId: $user->id,
                );
            } else {
                $workshop->expenses()->create([
                    'branch_id' => $product->branch_id,
                    'category' => 'Boshqa',
                    'title' => "Mahsulot sotib olish: {$product->name}",
                    'description' => "Boshlang'ich qoldiq: {$product->stock_quantity} {$product->unit}",
                    'amount' => $totalCost,
                    'expense_date' => now(),
                    'payment_method' => null,
                ]);
            }
        }

        return $product;
    }

    /**
     * Tanlangan global mahsulotlarni workshopga nusxa ko'chiradi. Allaqachon
     * nusxa olingan (global_product_id workshopda mavjud) elementlar
     * o'tkazib yuboriladi.
     *
     * @param  array<int, array{global_product_id: int, purchase_price?: float, selling_price?: float, stock_quantity?: int, min_stock_level?: int}>  $items
     * @return array{created: int, skipped: int}
     */
    public function copySelectionToWorkshop(Workshop $workshop, User $user, array $items, ?int $branchId): array
    {
        $alreadyCopied = $workshop->products()
            ->whereNotNull('global_product_id')
            ->pluck('global_product_id')
            ->all();

        $created = 0;
        $skipped = 0;

        $globalProducts = GlobalProduct::with('globalCategory')
            ->whereIn('id', collect($items)->pluck('global_product_id'))
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            if (in_array($item['global_product_id'], $alreadyCopied, true)) {
                $skipped++;
                continue;
            }

            $globalProduct = $globalProducts->get($item['global_product_id']);
            if (!$globalProduct) {
                continue;
            }

            $categoryId = null;
            if ($globalProduct->globalCategory) {
                $categoryName = $globalProduct->globalCategory->name;
                $categoryId = $workshop->categories()->firstOrCreate(
                    ['name' => $categoryName],
                    ['slug' => Str::slug($categoryName), 'is_active' => true]
                )->id;
            }

            $this->createWithInitialStock($workshop, $user, [
                'global_product_id' => $globalProduct->id,
                'category_id' => $categoryId,
                'name' => $globalProduct->name,
                'sku' => $globalProduct->sku,
                'description' => $globalProduct->description,
                'unit' => $globalProduct->unit,
                'barcode' => $globalProduct->barcode,
                'currency' => 'UZS',
                'purchase_price_uzs' => $item['purchase_price'] ?? 0,
                'selling_price_uzs' => $item['selling_price'] ?? 0,
                'purchase_price' => $item['purchase_price'] ?? 0,
                'selling_price' => $item['selling_price'] ?? 0,
                'stock_quantity' => $item['stock_quantity'] ?? 0,
                'min_stock_level' => $item['min_stock_level'] ?? 0,
                'is_active' => true,
                'track_inventory' => true,
                'branch_id' => $branchId,
            ]);

            $alreadyCopied[] = $globalProduct->id;
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function linkToGlobalCatalog(Product $product, Workshop $workshop): void
    {
        $globalProduct = $this->matcher->findOrCreateFor([
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'unit' => $product->unit,
            'description' => $product->description,
            'category_name' => $product->category?->name,
        ]);

        $alreadyLinkedInWorkshop = $workshop->products()
            ->where('id', '!=', $product->id)
            ->where('global_product_id', $globalProduct->id)
            ->exists();

        if ($alreadyLinkedInWorkshop) {
            return;
        }

        $product->update(['global_product_id' => $globalProduct->id]);
    }
}
