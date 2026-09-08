<?php

namespace Database\Seeders;

use App\Models\CarModel;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductSeeder extends Seeder
{
    /**
     * "Muhammadjon Aka Moy Antifriz" do'konidan olingan haqiqiy tan narx (~ dan oldin)
     * va sotuv narx (~ dan keyin) ma'lumotlari.
     *
     * Filtrlar har bir avtomobil turi uchun alohida (chunki qism raqami/o'lchami boshqa),
     * moylar esa umumiy mahsulot sifatida yaratilib, har bir avtomobil turiga
     * shu turning o'z motor moyi hajmi (CarModel::oil_capacity_liters) bilan bog'lanadi.
     */
    private const FILTERS = [
        'Chevrolet Tracker' => [
            ['name' => 'Motor moyi filtri (Tracker)', 'purchase' => 25000, 'selling' => 50000],
            ['name' => 'Xavo filtri (Tracker)', 'purchase' => 22000, 'selling' => 30000],
            ['name' => 'Salon filtri (Tracker)', 'purchase' => 15000, 'selling' => 30000],
        ],
        'Chevrolet Malibu' => [
            ['name' => 'Motor moyi filtri (Malibu)', 'purchase' => 25000, 'selling' => 50000],
            ['name' => 'Xavo filtri (Malibu)', 'purchase' => 50000, 'selling' => 70000],
            ['name' => 'Salon filtri (Malibu)', 'purchase' => 15000, 'selling' => 30000],
        ],
    ];

    /**
     * Har bir moy uchun narx va u qaysi avtomobil turlariga mos kelishi.
     * Narxi bir xil bo'lgan moylar (masalan, ikkala ro'yxatda ham 130 000~140 000
     * bo'lgan "Liqui Moly 5w30") bitta mahsulot sifatida ikkala turga bog'lanadi.
     */
    private const OILS = [
        [
            'name' => 'ACDelco 0W20',
            'purchase' => 80000,
            'selling' => 90000,
            'car_models' => ['Chevrolet Tracker'],
        ],
        [
            'name' => 'ACDelco 5W30',
            'purchase' => 70000,
            'selling' => 80000,
            'car_models' => ['Chevrolet Tracker'],
        ],
        [
            'name' => 'Rowe 0W20',
            'purchase' => 150000,
            'selling' => 160000,
            'car_models' => ['Chevrolet Tracker', 'Chevrolet Malibu'],
        ],
        [
            'name' => 'Rowe 5W30',
            'purchase' => 110000,
            'selling' => 120000,
            'car_models' => ['Chevrolet Tracker'],
        ],
        [
            'name' => 'Liqui Moly Molygen 5W30',
            'purchase' => 130000,
            'selling' => 140000,
            'car_models' => ['Chevrolet Tracker', 'Chevrolet Malibu'],
        ],
        [
            'name' => 'Liqui Moly 0W20',
            'purchase' => 150000,
            'selling' => 160000,
            'car_models' => ['Chevrolet Tracker', 'Chevrolet Malibu'],
        ],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Avtomobil turlari (motor moyi hajmi shu yerdan olinadi), rol/huquqlar
        $this->call(CarModelSeeder::class);
        $this->call(RolePermissionSeeder::class);

        // ============================================
        // 1. "Moy Antifriz" workshopi va egasi
        // ============================================
        $owner = User::firstOrCreate(
            ['phone' => '+998901112233'],
            [
                'name' => 'Muhammadjon Aka',
                'email' => 'muhammadjon@moyantifriz.uz',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'branch_id' => null,
            ]
        );
        $owner->assignRole('director');

        $workshop = Workshop::firstOrCreate(
            ['user_id' => $owner->id],
            [
                'name' => 'Muhammadjon Aka Moy Antifriz',
                'owner_name' => 'Muhammadjon Aka',
                'phone' => '+998901112233',
                'email' => 'info@moyantifriz.uz',
                'subscription_plan' => 'pro',
                'subscription_expires_at' => now()->addDays(365),
                'is_active' => true,
            ]
        );

        // ============================================
        // 2. Xodimlar (menejer va sotuvchi)
        //    Filial yo'q (bitta joydan ishlaydigan do'kon) — branch_id null
        //    bo'lsa, direktor yaratgan barcha yozuvlarni ham ko'ra oladi.
        // ============================================
        $manager = User::firstOrCreate(
            ['phone' => '+998901112234'],
            [
                'name' => 'Alisher Yusupov',
                'email' => 'alisher@moyantifriz.uz',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'branch_id' => null,
            ]
        );
        $manager->assignRole('manager');

        $employee = User::firstOrCreate(
            ['phone' => '+998901112235'],
            [
                'name' => 'Bekzod Qodirov',
                'email' => 'bekzod@moyantifriz.uz',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'branch_id' => null,
            ]
        );
        $employee->assignRole('employee');

        // ============================================
        // 3. Kategoriyalar
        // ============================================
        $categoryYog = Category::firstOrCreate(
            ['workshop_id' => $workshop->id, 'name' => 'Yog\'', 'type' => 'product'],
            ['is_active' => true]
        );

        $categoryFiltr = Category::firstOrCreate(
            ['workshop_id' => $workshop->id, 'name' => 'Filtrlar', 'type' => 'product'],
            ['is_active' => true]
        );

        // ============================================
        // 4. Kerakli avtomobil turlarini olish
        // ============================================
        $carModels = CarModel::whereIn('name', ['Tracker', 'Malibu'])
            ->whereHas('carMake', fn ($q) => $q->where('name', 'Chevrolet'))
            ->get()
            ->keyBy(fn (CarModel $carModel) => 'Chevrolet '.$carModel->name);

        // ============================================
        // 5. Filtrlar (har bir avtomobil turiga o'ziga xos, miqdori 1 dona)
        // ============================================
        foreach (self::FILTERS as $carModelKey => $filters) {
            $carModel = $carModels[$carModelKey] ?? null;

            foreach ($filters as $filter) {
                $product = Product::updateOrCreate(
                    ['workshop_id' => $workshop->id, 'name' => $filter['name']],
                    [
                        'category_id' => $categoryFiltr->id,
                        'unit' => 'dona',
                        'currency' => 'UZS',
                        'purchase_price' => $filter['purchase'],
                        'selling_price' => $filter['selling'],
                        'purchase_price_uzs' => $filter['purchase'],
                        'selling_price_uzs' => $filter['selling'],
                        'stock_quantity' => 20,
                        'is_active' => true,
                        'track_inventory' => true,
                    ]
                );

                if ($carModel) {
                    $product->localCarModels()->sync([$carModel->id => ['quantity' => 1]]);
                }
            }
        }

        // ============================================
        // 6. Moylar (umumiy mahsulot, hajmi avtomobil turiga qarab bog'lanadi)
        // ============================================
        foreach (self::OILS as $oil) {
            $product = Product::updateOrCreate(
                ['workshop_id' => $workshop->id, 'name' => $oil['name']],
                [
                    'category_id' => $categoryYog->id,
                    'unit' => 'litr',
                    'currency' => 'UZS',
                    'purchase_price' => $oil['purchase'],
                    'selling_price' => $oil['selling'],
                    'purchase_price_uzs' => $oil['purchase'],
                    'selling_price_uzs' => $oil['selling'],
                    'stock_quantity' => 20,
                    'is_active' => true,
                    'track_inventory' => true,
                ]
            );

            $pivotData = [];
            foreach ($oil['car_models'] as $carModelKey) {
                $carModel = $carModels[$carModelKey] ?? null;

                if ($carModel && $carModel->oil_capacity_liters !== null) {
                    $pivotData[$carModel->id] = ['quantity' => $carModel->oil_capacity_liters];
                }
            }

            if (!empty($pivotData)) {
                $product->localCarModels()->sync($pivotData);
            }
        }

        // ============================================
        // 7. Xulosa
        // ============================================
        $filtersCount = collect(self::FILTERS)->flatten(1)->count();
        $oilsCount = count(self::OILS);

        echo "\n✅ \"Moy Antifriz\" mahsulotlari muvaffaqiyatli yaratildi!\n\n";
        echo "📊 Yaratilgan ma'lumotlar:\n";
        echo "   - Workshop: {$workshop->name}\n";
        echo "   - Xodimlar: 3 ta (direktor, menejer, sotuvchi)\n";
        echo "   - Filtrlar: {$filtersCount} ta\n";
        echo "   - Moylar: {$oilsCount} ta\n\n";
        echo "🔐 Login ma'lumotlari (telefon raqam orqali):\n\n";
        echo "   Direktor (Muhammadjon Aka, hammasini ko'radi):\n";
        echo "     Telefon: +998901112233\n";
        echo "     Parol: password\n\n";
        echo "   Menejer (Alisher Yusupov):\n";
        echo "     Telefon: +998901112234\n";
        echo "     Parol: password\n\n";
        echo "   Sotuvchi/Xodim (Bekzod Qodirov, faqat savdo huquqi):\n";
        echo "     Telefon: +998901112235\n";
        echo "     Parol: password\n\n";
    }
}
