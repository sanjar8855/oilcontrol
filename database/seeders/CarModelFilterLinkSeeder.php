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
 *   ALIASES jadvali orqali topiladi:
 *     "MF 002 SPARK, COBALT, GENTRA"   → Spark, Cobalt, Gentra
 *     "MF 030 TOYOTA PRADO, LAND CRUISER 200" → Land Cruiser Prado + Land Cruiser
 *
 * MUHIM — MASKALASH:
 *   Aliaslar UZUNLIGI BO'YICHA kamayish tartibida tekshiriladi va topilgan
 *   bo'lak matndan O'CHIRILADI. Usiz "NEXIA 3" matni avval "NEXIA 3" ga,
 *   keyin yana "NEXIA" ga tushib, Nexia 3 filtri eski Nexia'ga ham
 *   bog'lanib ketardi. Xuddi shu muammo "MALIBU 2" / "MALIBU",
 *   "LAND CRUISER PRADO" / "LAND CRUISER", "TIGGO 8 PRO" / "TIGGO 8" da ham bor.
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
     * [alias, markasi ham matnda bo'lishi shartmi, [[marka, model], ...]].
     *
     * Tartib muhim emas — kod ishga tushishda alias uzunligi bo'yicha
     * o'zi saralaydi (uzunroq oldin).
     *
     * Ikkinchi ustun (requiresMake) — "K5", "M6", "X70", "HAN" kabi qisqa
     * aliaslar tasodifiy artikul kodlariga tushmasligi uchun marka nomini
     * ham talab qiladi.
     */
    private const ALIASES = [
        // ---------- Chevrolet ----------
        ['NEXIA 3', false, [['Chevrolet', 'Nexia 3']]],
        ['NEXIA R3', false, [['Chevrolet', 'Nexia 3']]],
        ['NEXIA 2', false, [['Chevrolet', 'Nexia 2']]],
        ['NEXIA 1', false, [['Chevrolet', 'Nexia 1']]],
        // Raqamsiz "Nexia" — O'zbekistonda odatda eski Nexia (1 va 2).
        // Nexia 3 har doim raqami bilan yoziladi, shuning uchun maskalash
        // tufayli bu qator Nexia 3 filtriga tegmaydi.
        ['NEXIA', false, [['Chevrolet', 'Nexia 1'], ['Chevrolet', 'Nexia 2']]],
        ['MALIBU 2', false, [['Chevrolet', 'Malibu 2']]],
        ['MALIBU XL', false, [['Chevrolet', 'Malibu 2']]],
        ['MALIBU 1', false, [['Chevrolet', 'Malibu']]],
        ['MALIBU', false, [['Chevrolet', 'Malibu']]],
        ['COBALT', false, [['Chevrolet', 'Cobalt']]],
        ['SPARK', false, [['Chevrolet', 'Spark']]],
        ['MATIZ', false, [['Chevrolet', 'Matiz']]],
        ['DAMAS', false, [['Chevrolet', 'Damas']]],
        ['LABO', false, [['Chevrolet', 'Labo']]],
        ['LACETTI', false, [['Chevrolet', 'Lacetti']]],
        ['GENTRA', false, [['Chevrolet', 'Gentra']]],
        ['ONIX', false, [['Chevrolet', 'Onix']]],
        ['TRACKER', false, [['Chevrolet', 'Tracker']]],
        ['CAPTIVA', false, [['Chevrolet', 'Captiva']]],
        ['MONZA', false, [['Chevrolet', 'Monza']]],
        ['EQUINOX', false, [['Chevrolet', 'Equinox']]],
        ['TRAILBLAZER', false, [['Chevrolet', 'TrailBlazer']]],

        // ---------- BYD ----------
        ['SONG PLUS CHAMPION', false, [['BYD', 'Song Plus Champion']]],
        ['SONG PLUS', false, [['BYD', 'Song Plus']]],
        ['YUAN UP', false, [['BYD', 'Yuan Up']]],
        ['QIN PLUS', false, [['BYD', 'Qin Plus']]],
        ['CHAZOR', false, [['BYD', 'Chazor']]],
        ['HAN', true, [['BYD', 'Han']]],
        ['TANG', true, [['BYD', 'Tang']]],
        ['SEAL', true, [['BYD', 'Seal']]],

        // ---------- Kia ----------
        ['K5', true, [['Kia', 'K5']]],
        ['SPORTAGE', false, [['Kia', 'Sportage']]],
        ['SORENTO', false, [['Kia', 'Sorento']]],
        ['SELTOS', false, [['Kia', 'Seltos']]],
        ['CERATO', false, [['Kia', 'Cerato (K3)']]],
        ['K3', true, [['Kia', 'Cerato (K3)']]],
        ['CARNIVAL', false, [['Kia', 'Carnival']]],

        // ---------- Chery ----------
        ['TIGGO 4', false, [['Chery', 'Tiggo 4']]],
        ['TIGGO 7 PRO', false, [['Chery', 'Tiggo 7 Pro']]],
        ['TIGGO 7', false, [['Chery', 'Tiggo 7 Pro']]],
        ['TIGGO 8 PRO', false, [['Chery', 'Tiggo 8 Pro']]],
        ['TIGGO 8', false, [['Chery', 'Tiggo 8 Pro']]],
        ['ARRIZO 5', false, [['Chery', 'Arrizo 5']]],
        ['ARRIZO 6', false, [['Chery', 'Arrizo 6']]],

        // ---------- Haval ----------
        ['JOLION', false, [['Haval', 'Jolion']]],
        ['M6', true, [['Haval', 'M6']]],
        ['F7', true, [['Haval', 'F7']]],
        ['H6', true, [['Haval', 'H6']]],

        // ---------- Hyundai ----------
        ['ELANTRA', false, [['Hyundai', 'Elantra']]],
        ['TUCSON', false, [['Hyundai', 'Tucson']]],
        ['SANTA FE', false, [['Hyundai', 'Santa Fe']]],
        ['SANTAFE', false, [['Hyundai', 'Santa Fe']]],
        ['SONATA', false, [['Hyundai', 'Sonata']]],
        ['ACCENT', false, [['Hyundai', 'Accent']]],

        // ---------- Lada (VAZ) ----------
        ['GRANTA', false, [['Lada (VAZ)', 'Granta']]],
        ['VESTA', false, [['Lada (VAZ)', 'Vesta']]],
        ['NIVA', false, [['Lada (VAZ)', 'Niva']]],
        ['XRAY', false, [['Lada (VAZ)', 'XRAY']]],

        // ---------- Toyota ----------
        ['LAND CRUISER PRADO', false, [['Toyota', 'Land Cruiser Prado']]],
        ['PRADO', false, [['Toyota', 'Land Cruiser Prado']]],
        ['LAND CRUISER', false, [['Toyota', 'Land Cruiser']]],
        ['CAMRY', false, [['Toyota', 'Camry']]],
        ['COROLLA', false, [['Toyota', 'Corolla']]],
        ['RAV4', false, [['Toyota', 'RAV4']]],
        ['HIGHLANDER', false, [['Toyota', 'Highlander']]],

        // ---------- Isuzu (yuk transporti) ----------
        // Prays-listlarda tonnaj bilan yoziladi: "ISUZU 5T", "ISUZU 10T"
        ['ISUZU AVTOBUS', false, [['Isuzu', 'Avtobus']]],
        ['ISUZU N SERIE', false, [['Isuzu', 'NPR (5T)']]],
        ['ISUZU 10 TN', false, [['Isuzu', 'FVR (10T)']]],
        ['ISUZU 2 5T', false, [['Isuzu', 'ELF (2.5T)']]],
        ['ISUZU 10T', false, [['Isuzu', 'FVR (10T)']]],
        ['ISUZU 5T', false, [['Isuzu', 'NPR (5T)'], ['Isuzu', 'NQR (5T)']]],
        ['NPR', false, [['Isuzu', 'NPR (5T)']]],
        ['NQR', false, [['Isuzu', 'NQR (5T)']]],
        ['FVR', false, [['Isuzu', 'FVR (10T)']]],

        // ---------- Jetour ----------
        ['X70 PLUS', true, [['Jetour', 'X70 Plus']]],
        ['X90 PLUS', true, [['Jetour', 'X90 Plus']]],
        ['X70', true, [['Jetour', 'X70']]],
        ['X90', true, [['Jetour', 'X90']]],
        ['X95', true, [['Jetour', 'X95']]],
        ['DASHING', true, [['Jetour', 'Dashing']]],
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
        'Toyota' => 'TOYOTA',
        'Isuzu' => 'ISUZU',
        'Jetour' => 'JETOUR',
    ];

    public function run(): void
    {
        $modelIds = CarModel::with('carMake')
            ->get()
            ->mapWithKeys(fn ($m) => [$m->carMake->name.'|'.$m->name => $m->id]);

        if ($modelIds->isEmpty()) {
            $this->command?->error("car_models bo'sh. Avval CarModelSeeder ni ishga tushiring.");

            return;
        }

        $products = GlobalProduct::whereIn('product_type', self::FILTER_TYPES)->get();

        if ($products->isEmpty()) {
            $this->command?->error('Filtr topilmadi. Avval GlobalCatalogSeeder ni ishga tushiring.');

            return;
        }

        // Uzun alias oldin tekshirilsin — maskalash to'g'ri ishlashi uchun shart
        $aliases = self::ALIASES;
        usort($aliases, fn ($a, $b) => mb_strlen($b[0]) <=> mb_strlen($a[0]));

        $linked = 0;
        $skipped = 0;
        $pairs = 0;

        DB::transaction(function () use ($products, $modelIds, $aliases, &$linked, &$skipped, &$pairs) {
            foreach ($products as $product) {
                $ids = $this->matchModelIds($product, $modelIds, $aliases);

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
        $this->command?->warn("   {$skipped} ta filtr bog'lanmadi — avtomobili car_models da yo'q");
        $this->command?->warn('   (Mercedes, Volvo, Howo, Shacman, Epica, Orlando, Cruze, Gazel, UAZ va h.k.).');
    }

    /**
     * Mahsulot nomi va fits_models matnidan mos car_model ID larini topadi.
     *
     * @param  array<int, array{0: string, 1: bool, 2: array<int, array{0: string, 1: string}>}>  $aliases
     * @return array<int, int>
     */
    private function matchModelIds(GlobalProduct $product, \Illuminate\Support\Collection $modelIds, array $aliases): array
    {
        $text = mb_strtoupper($product->name.' '.($product->fits_models ?? ''));
        // Tinish belgilarini probelga almashtiramiz: "NEXIA 3 \"AVEO\"" → "NEXIA 3 AVEO"
        $text = (string) preg_replace('/[^A-Z0-9]+/u', ' ', $text);
        $text = ' '.trim((string) preg_replace('/\s+/', ' ', $text)).' ';

        $ids = [];

        foreach ($aliases as [$alias, $requiresMake, $models]) {
            if ($requiresMake && ! str_contains($text, ' '.self::MAKE_KEYWORDS[$models[0][0]].' ')) {
                continue;
            }

            // So'z chegarasi: "SPARK" "SPARKLE" ga, "K5" "MK55" ga tushmasin
            $pattern = '/(?<![A-Z0-9])'.preg_quote($alias, '/').'(?![A-Z0-9])/';

            if (! preg_match($pattern, $text)) {
                continue;
            }

            foreach ($models as [$make, $model]) {
                $id = $modelIds->get($make.'|'.$model);

                if ($id !== null) {
                    $ids[$id] = $id;
                }
            }

            // MASKALASH: topilgan bo'lakni matndan o'chiramiz, aks holda
            // qisqaroq alias ("NEXIA") shu joyga yana tushib qoladi.
            $text = (string) preg_replace($pattern, ' ', $text);
        }

        return array_values($ids);
    }
}
