<?php

namespace Database\Seeders;

use App\Models\CarModel;
use App\Models\GlobalProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Filtrlarni avtomobil modellariga bog'laydi (car_model_global_products).
 *
 * Maqsad: usta avtomobilni tanlaganda tizim mos filtrni o'zi taklif qilsin —
 * "Cobalt'ga qaysi moy filtri?" degan savolga tizim javob bersin.
 *
 * QANDAY ISHLAYDI:
 *   Filtr nomi va `fits_models` ustunidagi matndan avtomobil modellari
 *   ALIASES jadvali orqali topiladi. Masalan:
 *     "MF 002 SPARK, COBALT, GENTRA"  → Spark, Cobalt, Gentra
 *     "WIX WA9633" + fits_models="Toyota Camry 40" → (Toyota katalogda yo'q, o'tkazib yuboriladi)
 *
 * IDEMPOTENT: syncWithoutDetaching ishlatiladi — qayta ishga tushirilganda
 * dublikat yaratmaydi va qo'lda qo'shilgan bog'lanishlarni O'CHIRMAYDI.
 *
 * Ishga tushirish:
 *   php artisan db:seed --class=Database\\Seeders\\CarModelFilterLinkSeeder
 */
class CarModelFilterLinkSeeder extends Seeder
{
    /** Bog'lanadigan mahsulot turlari. */
    private const FILTER_TYPES = ['oil_filter', 'air_filter', 'cabin_filter', 'fuel_filter'];

    /**
     * [matndagi alias, marka, model, markasi ham matnda bo'lishi shartmi].
     *
     * TARTIB MUHIM: uzunroq alias oldin turishi kerak, aks holda
     * "NEXIA 3" matni "NEXIA" ga tushib qoladi.
     *
     * Oxirgi ustun (requiresMake) — "K5", "M6", "HAN" kabi qisqa aliaslar
     * tasodifiy kodlarga tushmasligi uchun marka nomini ham talab qiladi.
     */
    private const ALIASES = [
        // Chevrolet
        ['NEXIA 3', 'Chevrolet', 'Nexia 3', false],
        ['NEXIA R3', 'Chevrolet', 'Nexia 3', false],
        ['NEXIA 2', 'Chevrolet', 'Nexia 2', false],
        ['NEXIA 1', 'Chevrolet', 'Nexia 1', false],
        // Raqamsiz "Nexia" — O'zbekistonda odatda eski Nexia (1 va 2).
        // Nexia 3 har doim raqami bilan yoziladi.
        ['NEXIA', 'Chevrolet', 'Nexia 1', false],
        ['NEXIA', 'Chevrolet', 'Nexia 2', false],
        ['MALIBU 2', 'Chevrolet', 'Malibu 2', false],
        ['MALIBU XL', 'Chevrolet', 'Malibu 2', false],
        ['MALIBU 1', 'Chevrolet', 'Malibu', false],
        ['MALIBU', 'Chevrolet', 'Malibu', false],
        ['COBALT', 'Chevrolet', 'Cobalt', false],
        ['SPARK', 'Chevrolet', 'Spark', false],
        ['MATIZ', 'Chevrolet', 'Matiz', false],
        ['DAMAS', 'Chevrolet', 'Damas', false],
        ['LABO', 'Chevrolet', 'Labo', false],
        ['LACETTI', 'Chevrolet', 'Lacetti', false],
        ['GENTRA', 'Chevrolet', 'Gentra', false],
        ['ONIX', 'Chevrolet', 'Onix', false],
        ['TRACKER', 'Chevrolet', 'Tracker', false],
        ['CAPTIVA', 'Chevrolet', 'Captiva', false],
        ['MONZA', 'Chevrolet', 'Monza', false],
        ['EQUINOX', 'Chevrolet', 'Equinox', false],
        ['TRAILBLAZER', 'Chevrolet', 'TrailBlazer', false],
        // BYD
        ['SONG PLUS CHAMPION', 'BYD', 'Song Plus Champion', false],
        ['SONG PLUS', 'BYD', 'Song Plus', false],
        ['YUAN UP', 'BYD', 'Yuan Up', false],
        ['QIN PLUS', 'BYD', 'Qin Plus', false],
        ['CHAZOR', 'BYD', 'Chazor', false],
        ['HAN', 'BYD', 'Han', true],
        ['TANG', 'BYD', 'Tang', true],
        ['SEAL', 'BYD', 'Seal', true],
        // Kia
        ['K5', 'Kia', 'K5', true],
        ['SPORTAGE', 'Kia', 'Sportage', false],
        ['SORENTO', 'Kia', 'Sorento', false],
        ['SELTOS', 'Kia', 'Seltos', false],
        ['CERATO', 'Kia', 'Cerato (K3)', false],
        ['K3', 'Kia', 'Cerato (K3)', true],
        ['CARNIVAL', 'Kia', 'Carnival', false],
        // Chery
        ['TIGGO 4', 'Chery', 'Tiggo 4', false],
        ['TIGGO 7 PRO', 'Chery', 'Tiggo 7 Pro', false],
        ['TIGGO 7', 'Chery', 'Tiggo 7 Pro', false],
        ['TIGGO 8 PRO', 'Chery', 'Tiggo 8 Pro', false],
        ['TIGGO 8', 'Chery', 'Tiggo 8 Pro', false],
        ['ARRIZO 5', 'Chery', 'Arrizo 5', false],
        ['ARRIZO 6', 'Chery', 'Arrizo 6', false],
        // Haval
        ['JOLION', 'Haval', 'Jolion', false],
        ['M6', 'Haval', 'M6', true],
        ['F7', 'Haval', 'F7', true],
        ['H6', 'Haval', 'H6', true],
        // Hyundai
        ['ELANTRA', 'Hyundai', 'Elantra', false],
        ['TUCSON', 'Hyundai', 'Tucson', false],
        ['SANTA FE', 'Hyundai', 'Santa Fe', false],
        ['SANTAFE', 'Hyundai', 'Santa Fe', false],
        ['SONATA', 'Hyundai', 'Sonata', false],
        ['ACCENT', 'Hyundai', 'Accent', false],
        // Lada (VAZ)
        ['GRANTA', 'Lada (VAZ)', 'Granta', false],
        ['VESTA', 'Lada (VAZ)', 'Vesta', false],
        ['NIVA', 'Lada (VAZ)', 'Niva', false],
        ['XRAY', 'Lada (VAZ)', 'XRAY', false],
    ];

    /** requiresMake=true bo'lganda matnda izlanadigan marka so'zi. */
    private const MAKE_KEYWORDS = [
        'Chevrolet' => 'CHEVROLET',
        'BYD' => 'BYD',
        'Kia' => 'KIA',
        'Chery' => 'CHERY',
        'Haval' => 'HAVAL',
        'Hyundai' => 'HYUNDAI',
        'Lada (VAZ)' => 'LADA',
    ];

    public function run(): void
    {
        $modelIds = CarModel::with('carMake')
            ->get()
            ->mapWithKeys(fn ($m) => [$m->carMake->name.'|'.$m->name => $m->id]);

        if ($modelIds->isEmpty()) {
            $this->command?->error('car_models bo\'sh. Avval CarModelSeeder ni ishga tushiring.');

            return;
        }

        $products = GlobalProduct::whereIn('product_type', self::FILTER_TYPES)->get();

        if ($products->isEmpty()) {
            $this->command?->error('Filtr topilmadi. Avval GlobalCatalogSeeder ni ishga tushiring.');

            return;
        }

        $linked = 0;
        $skipped = 0;
        $pairs = 0;

        DB::transaction(function () use ($products, $modelIds, &$linked, &$skipped, &$pairs) {
            foreach ($products as $product) {
                $ids = $this->matchModelIds($product, $modelIds);

                if (empty($ids)) {
                    $skipped++;

                    continue;
                }

                $product->carModels()->syncWithoutDetaching(
                    array_fill_keys($ids, ['quantity' => 1])
                );

                $linked++;
                $pairs += count($ids);
            }
        });

        $this->command?->info("✅ Filtr ↔ avtomobil bog'lanishi: {$linked} ta filtr, {$pairs} ta bog'lanish.");
        $this->command?->warn("   {$skipped} ta filtr bog'lanmadi — ularning avtomobili car_models da yo'q");
        $this->command?->warn('   (Toyota, Isuzu, Mercedes, Volvo, Jetour va h.k.).');
    }

    /**
     * Mahsulot nomi va fits_models matnidan mos car_model ID larini topadi.
     *
     * @return array<int, int>
     */
    private function matchModelIds(GlobalProduct $product, \Illuminate\Support\Collection $modelIds): array
    {
        $text = mb_strtoupper($product->name.' '.($product->fits_models ?? ''));
        // Tinish belgilarini probelga almashtiramiz: "NEXIA 3 \"AVEO\"" → "NEXIA 3 AVEO"
        $text = preg_replace('/[^A-Z0-9]+/u', ' ', $text);
        $text = ' '.trim((string) preg_replace('/\s+/', ' ', $text)).' ';

        $ids = [];

        foreach (self::ALIASES as [$alias, $make, $model, $requiresMake]) {
            if ($requiresMake && ! str_contains($text, ' '.self::MAKE_KEYWORDS[$make].' ')) {
                continue;
            }

            // So'z chegarasi: "SPARK" "SPARKLE" ga tushmasin, "K5" "MK55" ga tushmasin
            if (! preg_match('/(?<![A-Z0-9])'.preg_quote($alias, '/').'(?![A-Z0-9])/', $text)) {
                continue;
            }

            $id = $modelIds->get($make.'|'.$model);

            if ($id !== null) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
