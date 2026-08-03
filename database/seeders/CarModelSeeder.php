<?php

namespace Database\Seeders;

use App\Models\CarMake;
use Illuminate\Database\Seeder;

class CarModelSeeder extends Seeder
{
    /**
     * O'zbekistonda aktiv sotuvdagi avtomobil turlari, markasi bo'yicha guruhlangan.
     * Har bir tur uchun taxminiy texnik xizmat ko'rsatuv ma'lumotlari:
     * [motor moyi hajmi (litr), antifriz min (litr), antifriz max (litr)].
     * Bu qiymatlar odatiy servis kitobchalariga asoslangan taxminiy son bo'lib,
     * "Avto markalari" sahifasida har bir marka/model uchun alohida tahrirlash mumkin.
     */
    private const CAR_MODELS_BY_MAKE = [
        'Chevrolet' => [
            'Cobalt' => [3.2, 5.7, 6.0],
            'Nexia 1' => [3.5, 6.0, 6.5],
            'Nexia 2' => [3.8, 5.0, 7.0],
            'Nexia 3' => [3.5, 6.5, 7.0],
            'Malibu' => [4.7, 7.0, 7.5],
            'Malibu 2' => [4.5, 7.5, 8.0],
            'Spark' => [2.8, 5.0, 5.5],
            'Matiz' => [2.8, 4.5, 5.0],
            'Damas' => [2.8, 5.0, 5.5],
            'Labo' => [2.8, 5.0, 5.5],
            'Lacetti' => [3.8, 6.5, 7.0],
            'Gentra' => [3.5, 6.5, 7.0],
            'Onix' => [3.2, 5.7, 6.2],
            'Tracker' => [4.2, 6.5, 7.0],
            'Captiva' => [5.0, 7.5, 8.5],
            'Monza' => [3.2, 6.0, 6.5],
            'Equinox' => [4.7, 7.0, 7.5],
            'TrailBlazer' => [4.7, 9.0, 10.0],
        ],
        'BYD' => [
            'Song Plus' => [4.0, 7.0, 8.0],
            'Song Plus Champion' => [4.0, 7.0, 8.0],
            'Chazor' => [4.0, 7.0, 8.0],
            'Yuan Up' => [3.5, 6.0, 7.0],
            'Qin Plus' => [3.7, 6.5, 7.0],
            'Han' => [null, 8.0, 9.0],
            'Tang' => [5.5, 9.0, 10.0],
            'Seal' => [null, 9.0, 9.5],
        ],
        'Kia' => [
            'K5' => [4.6, 7.0, 7.5],
            'Sportage' => [4.6, 8.0, 8.5],
            'Sorento' => [5.6, 9.0, 9.5],
            'Seltos' => [4.3, 6.5, 7.0],
            'Cerato (K3)' => [4.3, 6.5, 7.0],
            'Carnival' => [5.6, 9.0, 9.5],
        ],
        'Chery' => [
            'Tiggo 4' => [3.2, 6.0, 6.5],
            'Tiggo 7 Pro' => [4.5, 7.0, 7.5],
            'Tiggo 8 Pro' => [4.7, 8.0, 8.5],
            'Arrizo 5' => [3.5, 6.5, 7.0],
            'Arrizo 6' => [4.0, 7.0, 7.5],
        ],
        'Haval' => [
            'Jolion' => [4.3, 7.0, 7.5],
            'M6' => [3.7, 6.5, 7.0],
            'F7' => [4.5, 7.5, 8.0],
            'H6' => [4.5, 7.5, 8.5],
        ],
        'Hyundai' => [
            'Elantra' => [3.8, 6.4, 7.0],
            'Tucson' => [4.3, 8.0, 9.0],
            'Santa Fe' => [5.9, 9.0, 10.0],
            'Sonata' => [4.5, 7.0, 7.5],
            'Accent' => [3.3, 6.0, 6.5],
        ],
        'Lada (VAZ)' => [
            'Granta' => [3.5, 7.4, 7.8],
            'Vesta' => [3.5, 7.5, 8.0],
            'Niva' => [3.8, 9.8, 10.5],
            'XRAY' => [3.5, 7.5, 8.0],
        ],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (self::CAR_MODELS_BY_MAKE as $makeName => $models) {
            $make = CarMake::firstOrCreate(['name' => $makeName]);

            foreach ($models as $modelName => [$oil, $antifreezeMin, $antifreezeMax]) {
                $make->carModels()->updateOrCreate(
                    ['name' => $modelName],
                    [
                        'oil_capacity_liters' => $oil,
                        'antifreeze_capacity_min_liters' => $antifreezeMin,
                        'antifreeze_capacity_max_liters' => $antifreezeMax,
                    ]
                );
            }
        }
    }
}
