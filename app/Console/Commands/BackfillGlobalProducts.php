<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\GlobalProductMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillGlobalProducts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'global-catalog:backfill';

    /**
     * @var string
     */
    protected $description = 'Global_product_id\'siz mahsulotlarni umumiy katalog bilan moslashtiradi/qo\'shadi va eski car_model_products bog\'lanishlarini global darajaga ko\'chiradi. Bir necha marta xavfsiz ishga tushirish mumkin.';

    public function handle(GlobalProductMatcher $matcher): int
    {
        $this->info('Global katalogga bog\'lanmagan mahsulotlarni aniqlash...');

        $linkedProducts = 0;

        DB::transaction(function () use ($matcher, &$linkedProducts) {
            Product::whereNull('global_product_id')
                ->with('category')
                ->chunkById(50, function ($products) use ($matcher, &$linkedProducts) {
                    foreach ($products as $product) {
                        $globalProduct = $matcher->findOrCreateFor([
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'barcode' => $product->barcode,
                            'unit' => $product->unit,
                            'description' => $product->description,
                            'category_name' => $product->category?->name,
                        ]);

                        $alreadyLinkedInWorkshop = Product::where('workshop_id', $product->workshop_id)
                            ->where('id', '!=', $product->id)
                            ->where('global_product_id', $globalProduct->id)
                            ->exists();

                        if ($alreadyLinkedInWorkshop) {
                            continue;
                        }

                        $product->update(['global_product_id' => $globalProduct->id]);
                        $linkedProducts++;
                    }
                });

            DB::table('car_model_products')
                ->join('products', 'products.id', '=', 'car_model_products.product_id')
                ->whereNotNull('products.global_product_id')
                ->select('car_model_products.car_model_id', 'products.global_product_id', 'car_model_products.quantity')
                ->get()
                ->groupBy(fn ($row) => $row->car_model_id.'-'.$row->global_product_id)
                ->each(function ($rows) {
                    $first = $rows->first();

                    DB::table('car_model_global_products')->updateOrInsert(
                        ['car_model_id' => $first->car_model_id, 'global_product_id' => $first->global_product_id],
                        ['quantity' => $rows->max('quantity'), 'updated_at' => now(), 'created_at' => now()]
                    );
                });
        });

        $migratedLinks = DB::table('car_model_global_products')->count();

        $this->info("Yakunlandi: {$linkedProducts} ta mahsulot global katalogga bog'landi, jami {$migratedLinks} ta moshina-mahsulot bog'lanishi mavjud.");

        return self::SUCCESS;
    }
}
