<?php

namespace Database\Seeders;

use App\Models\CarMake;
use Illuminate\Database\Seeder;

class CarModelSeeder extends Seeder
{
    /**
     * O'zbekistonda aktiv sotuvdagi avtomobil turlari (nomlari), markasi bo'yicha guruhlangan.
     */
    private const CAR_MODELS_BY_MAKE = [
        'Chevrolet' => [
            'Cobalt', 'Nexia 1', 'Nexia 2', 'Nexia 3', 'Malibu', 'Malibu 2', 'Spark', 'Matiz',
            'Damas', 'Labo', 'Lacetti', 'Gentra', 'Onix', 'Tracker',
            'Captiva', 'Monza', 'Equinox', 'TrailBlazer',
        ],
        'BYD' => [
            'Song Plus', 'Song Plus Champion', 'Chazor', 'Yuan Up',
            'Qin Plus', 'Han', 'Tang', 'Seal',
        ],
        'Kia' => [
            'K5', 'Sportage', 'Sorento', 'Seltos',
            'Cerato (K3)', 'Carnival',
        ],
        'Chery' => ['Tiggo 4', 'Tiggo 7 Pro', 'Tiggo 8 Pro', 'Arrizo 5', 'Arrizo 6'],
        'Haval' => ['Jolion', 'M6', 'F7', 'H6'],
        'Hyundai' => ['Elantra', 'Tucson', 'Santa Fe', 'Sonata', 'Accent'],
        'Lada (VAZ)' => ['Granta', 'Vesta', 'Niva', 'XRAY'],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (self::CAR_MODELS_BY_MAKE as $makeName => $models) {
            $make = CarMake::firstOrCreate(['name' => $makeName]);

            foreach ($models as $modelName) {
                $make->carModels()->firstOrCreate(['name' => $modelName]);
            }
        }
    }
}
