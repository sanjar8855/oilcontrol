<?php

namespace App\Services;

use App\Models\GlobalCategory;
use App\Models\GlobalProduct;

class GlobalProductMatcher
{
    /**
     * Berilgan mahsulot ma'lumotlariga mos GlobalProduct'ni topadi yoki
     * topilmasa yangisini yaratadi. Avval barcode, keyin SKU, keyin
     * normallashtirilgan (trim+lowercase) nom bo'yicha aniq moslik
     * qidiriladi — amaliyotda moy almashtirish shahobchalari barcode/SKU
     * kiritmasligi mumkin, shu sabab nom asosiy mezon bo'lib qoladi.
     */
    public function findOrCreateFor(array $data): GlobalProduct
    {
        $barcode = trim((string) ($data['barcode'] ?? ''));
        if ($barcode !== '') {
            $match = GlobalProduct::where('barcode', $barcode)->first();
            if ($match) {
                return $match;
            }
        }

        $sku = trim((string) ($data['sku'] ?? ''));
        if ($sku !== '') {
            $match = GlobalProduct::where('sku', $sku)->first();
            if ($match) {
                return $match;
            }
        }

        $normalizedName = $this->normalizeName($data['name']);
        $match = GlobalProduct::whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])->first();
        if ($match) {
            return $match;
        }

        return GlobalProduct::create([
            'global_category_id' => $this->resolveCategoryId($data['category_name'] ?? null),
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'unit' => $data['unit'],
            'barcode' => $data['barcode'] ?? null,
            'is_active' => true,
        ]);
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    private function resolveCategoryId(?string $categoryName): ?int
    {
        $categoryName = trim((string) $categoryName);

        if ($categoryName === '') {
            return null;
        }

        return GlobalCategory::firstOrCreate(['name' => $categoryName])->id;
    }
}
