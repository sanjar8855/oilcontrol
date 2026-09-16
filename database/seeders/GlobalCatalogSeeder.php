<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\GlobalCategory;
use App\Models\GlobalProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Global katalogni yetkazib beruvchilarning narx-ro'yxatlaridan to'ldiradi.
 *
 * Manbalar (database/data/global_catalog/*.json):
 *   enoc_epsent.json — price 01.04.2026.xlsx        (ENOC, EPSENT, SDK, Lubrent, KB ...)
 *   oilux.json       — Oilux Price All 21.08.2026    (Aveno, Hyundai Xteer, Korelux, Zitron + filtrlar)
 *   oil_hub.json     — OIL HUB price list 01.04.2026 (Everest, Mobil, Shell, Castrol)
 *   qoqon_1c.json    — 1C QO'QON 07.09.2026          (Fosser, Basinol, Liqui Moly, Fuchs, SRS, Speedmate)
 *
 * IDEMPOTENT: kalit — global_products.sku. Qayta ishga tushirsa dublikat
 * yaratmaydi, mavjud yozuvni yangilaydi. Shuning uchun narx yangilanganda
 * ham shu seederni qayta chaqirish mumkin.
 *
 * Ishga tushirish:
 *   php artisan db:seed --class=Database\\Seeders\\GlobalCatalogSeeder
 */
class GlobalCatalogSeeder extends Seeder
{
    private const CHUNK = 200;

    public function run(): void
    {
        $files = glob(database_path('data/global_catalog/*.json'));

        if (empty($files)) {
            $this->command?->error('database/data/global_catalog/ ichida JSON fayl topilmadi.');

            return;
        }

        $rows = [];
        foreach ($files as $file) {
            $decoded = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $rows = array_merge($rows, $decoded);
            $this->command?->info(basename($file).' — '.count($decoded).' ta pozitsiya');
        }

        // sku bo'yicha yakuniy deduplikatsiya (fayllar orasida kesishuv bo'lsa)
        $rows = collect($rows)->keyBy('sku')->values();

        $brandIds = $this->syncLookup(Brand::class, $rows->pluck('brand'));
        $categoryIds = $this->syncLookup(GlobalCategory::class, $rows->pluck('category'));

        $now = now();
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, $brandIds, $categoryIds, $now, &$created, &$updated) {
            foreach ($rows->chunk(self::CHUNK) as $chunk) {
                foreach ($chunk as $row) {
                    $product = GlobalProduct::updateOrCreate(
                        ['sku' => $row['sku']],
                        [
                            'global_category_id' => $categoryIds[$row['category']] ?? null,
                            'brand_id' => $brandIds[$row['brand']] ?? null,
                            'name' => $row['name'],
                            'description' => $row['description'] ?? null,
                            'unit' => $row['unit'] ?? 'dona',
                            'product_type' => $row['product_type'] ?? null,
                            'viscosity' => $row['viscosity'] ?? null,
                            'oil_base' => $row['oil_base'] ?? null,
                            'api_spec' => $row['api_spec'] ?? null,
                            'volume_liters' => $row['volume_liters'] ?? null,
                            'pack_qty' => $row['pack_qty'] ?? null,
                            'supplier_code' => $row['supplier_code'] ?? null,
                            'source' => $row['source'] ?? null,
                            'recommended_price' => $row['recommended_price'] ?? null,
                            'currency' => $row['currency'] ?? 'USD',
                            'price_updated_at' => isset($row['recommended_price']) ? $now : null,
                            'is_active' => true,
                        ]
                    );

                    $product->wasRecentlyCreated ? $created++ : $updated++;
                }
            }
        });

        $this->command?->info("✅ Global katalog: {$created} ta yangi, {$updated} ta yangilandi.");
        $this->command?->info('   Brendlar: '.count($brandIds).' | Kategoriyalar: '.count($categoryIds));
    }

    /**
     * Nomlar ro'yxatini lookup jadvaliga sync qiladi va [nom => id] qaytaradi.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function syncLookup(string $model, \Illuminate\Support\Collection $names): array
    {
        $names = $names->filter()->unique()->values();
        $now = now();

        $model::upsert(
            $names->map(fn ($name) => [
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['name'],
            ['is_active', 'updated_at']
        );

        return $model::whereIn('name', $names)->pluck('id', 'name')->all();
    }
}
