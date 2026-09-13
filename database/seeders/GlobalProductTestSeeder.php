<?php

namespace Database\Seeders;

use App\Models\CarModel;
use App\Models\GlobalCategory;
use App\Models\GlobalProduct;
use Illuminate\Database\Seeder;

class GlobalProductTestSeeder extends Seeder
{
    /**
     * Onboarding oqimini sinash uchun 3 ta mahsulot — Motor moyi, Havo
     * filtri, Moy filtri, barchasi Chevrolet Cobalt modeliga bog'lanadi.
     * Bu ro'yxat OnboardingController::STARTER_PRODUCT_NAMES bilan mos
     * kelishi kerak.
     */
    private const TYPES = [
        ['name' => 'Motor moyi', 'unit' => 'litr', 'category' => 'Moy'],
        ['name' => 'Havo filtri', 'unit' => 'dona', 'category' => 'Xavo Filtr'],
        ['name' => 'Moy filtri', 'unit' => 'dona', 'category' => 'Motor moyi filtr'],
    ];

    private const CAR_MODELS = ['Cobalt'];

    public function run(): void
    {
        $carModels = CarModel::whereIn('name', self::CAR_MODELS)
            ->whereHas('carMake', fn ($q) => $q->where('name', 'Chevrolet'))
            ->get()
            ->keyBy('name');

        foreach (self::TYPES as $type) {
            $categoryId = GlobalCategory::firstOrCreate(['name' => $type['category']])->id;

            foreach (self::CAR_MODELS as $modelName) {
                $carModel = $carModels->get($modelName);
                $name = "{$type['name']} ({$modelName})";

                $globalProduct = GlobalProduct::updateOrCreate(
                    ['name' => $name],
                    [
                        'global_category_id' => $categoryId,
                        'unit' => $type['unit'],
                        'is_active' => true,
                    ]
                );

                if ($carModel) {
                    $globalProduct->carModels()->syncWithoutDetaching([$carModel->id => ['quantity' => 1]]);
                }
            }
        }

        echo "\n✅ Onboarding uchun 3 ta test mahsuloti yaratildi (Motor moyi/Havo filtri/Moy filtri, Cobalt uchun).\n";
    }
}
