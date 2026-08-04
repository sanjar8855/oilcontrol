<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Client;
use App\Models\Reminder;
use App\Models\ServiceLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ============================================
        // 0. Avtomobil markalari va turlari, rol/huquqlar
        // ============================================
        $this->call(CarModelSeeder::class);
        $this->call(RolePermissionSeeder::class);

        // ============================================
        // 1. SuperAdmin yaratish
        // ============================================
        $superadmin = User::create([
            'name' => 'SuperAdmin',
            'phone' => '+998937058855',
            'email' => 'superadmin@oilcontrol.uz',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'branch_id' => null,
        ]);
        $superadmin->assignRole('superadmin');

        // ============================================
        // 2. Tadbirkor (Director) va Workshop yaratish
        // ============================================
        $director = User::create([
            'name' => 'Sardor Toshmatov',
            'phone' => '+998901234567',
            'email' => 'director@oilcontrol.uz',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'branch_id' => null, // Director barcha filiallarni ko'radi
        ]);
        $director->assignRole('director');

        $workshop = Workshop::create([
            'user_id' => $director->id,
            'name' => 'Universal Avto Servis',
            'owner_name' => 'Sardor Toshmatov',
            'phone' => '+998901234567',
            'email' => 'info@universal-avto.uz',
            'address' => 'Toshkent shahar, Yunusobod tumani',
            'subscription_plan' => 'pro',
            'subscription_expires_at' => now()->addDays(365),
            'is_active' => true,
        ]);

        // ============================================
        // 3. Filiallar yaratish
        // ============================================
        $branch1 = Branch::create([
            'workshop_id' => $workshop->id,
            'name' => 'Yunusobod filiali',
            'code' => 'F001',
            'address' => 'Toshkent shahar, Yunusobod tumani, Amir Temur ko\'chasi 15',
            'phone' => '+998905555001',
            'email' => 'yunusobod@universal-avto.uz',
            'is_active' => true,
        ]);

        $branch2 = Branch::create([
            'workshop_id' => $workshop->id,
            'name' => 'Chilonzor filiali',
            'code' => 'F002',
            'address' => 'Toshkent shahar, Chilonzor tumani, Bunyodkor ko\'chasi 25',
            'phone' => '+998905555002',
            'email' => 'chilonzor@universal-avto.uz',
            'is_active' => true,
        ]);

        // ============================================
        // 4. Filial xodimlari yaratish
        // ============================================

        // Yunusobod filiali
        $manager1 = User::create([
            'name' => 'Aziz Rahmonov',
            'phone' => '+998905555001',
            'email' => 'manager1@oilcontrol.uz',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'branch_id' => $branch1->id,
        ]);
        $manager1->assignRole('manager');

        $employee1 = User::create([
            'name' => 'Jamshid Karimov',
            'phone' => '+998905555011',
            'email' => 'employee1@oilcontrol.uz',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'branch_id' => $branch1->id,
        ]);
        $employee1->assignRole('employee');

        // Chilonzor filiali
        $manager2 = User::create([
            'name' => 'Bobur Aliyev',
            'phone' => '+998905555002',
            'email' => 'manager2@oilcontrol.uz',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'branch_id' => $branch2->id,
        ]);
        $manager2->assignRole('manager');

        $employee2 = User::create([
            'name' => 'Rustam Usmonov',
            'phone' => '+998905555022',
            'email' => 'employee2@oilcontrol.uz',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'branch_id' => $branch2->id,
        ]);
        $employee2->assignRole('employee');

        // ============================================
        // 5. Kategoriyalar yaratish
        // ============================================
        $categoryYog = Category::create([
            'workshop_id' => $workshop->id,
            'name' => 'Yog\'',
            'type' => 'product',
            'is_active' => true,
        ]);

        $categoryFilter = Category::create([
            'workshop_id' => $workshop->id,
            'name' => 'Filtrlar',
            'type' => 'product',
            'is_active' => true,
        ]);

        $categoryQismlar = Category::create([
            'workshop_id' => $workshop->id,
            'name' => 'Ehtiyot qismlar',
            'type' => 'product',
            'is_active' => true,
        ]);

        // Xarajat kategoriyalari
        $expenseElektr = Category::create([
            'workshop_id' => $workshop->id,
            'name' => 'Elektr',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $expenseIshHaqi = Category::create([
            'workshop_id' => $workshop->id,
            'name' => 'Ish haqi',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $expenseIjara = Category::create([
            'workshop_id' => $workshop->id,
            'name' => 'Ijara',
            'type' => 'expense',
            'is_active' => true,
        ]);

        // ============================================
        // 6. Yunusobod filiali uchun ma'lumotlar
        // ============================================

        // Mijozlar
        $client1 = Client::create([
            'workshop_id' => $workshop->id,
            'branch_id' => $branch1->id,
            'name' => 'Abbos Karimov',
            'phone' => '+998901111111',
            'telegram_id' => '123456789',
            'email' => 'abbos@gmail.com',
            'notes' => 'Doimiy mijoz',
        ]);

        $client2 = Client::create([
            'workshop_id' => $workshop->id,
            'branch_id' => $branch1->id,
            'name' => 'Dilshod Tursunov',
            'phone' => '+998902222222',
            'telegram_id' => '987654321',
            'email' => 'dilshod@mail.ru',
            'notes' => 'Taksi haydovchisi',
        ]);

        // Avtomobillar
        $vehicle1 = Vehicle::create([
            'client_id' => $client1->id,
            'make' => 'Chevrolet',
            'model' => 'Lacetti',
            'year' => 2015,
            'plate_number' => '01 A 123 BC',
            'vin' => 'KL1SF68Y38B123456',
        ]);

        $vehicle2 = Vehicle::create([
            'client_id' => $client2->id,
            'make' => 'Daewoo',
            'model' => 'Gentra',
            'year' => 2018,
            'plate_number' => '01 B 456 DE',
            'vin' => 'XWB3L32EDJB654321',
        ]);

        // Servis yozuvlari
        ServiceLog::create([
            'vehicle_id' => $vehicle1->id,
            'branch_id' => $branch1->id,
            'service_date' => now()->subDays(10),
            'odometer_reading' => 85000,
            'next_service_km' => 5000,
            'avg_monthly_km' => 800,
            'service_type' => 'oil_change',
            'cost' => 450000,
            'labor_cost' => 150000,
            'notes' => 'Yog\' va filter almashtirildi',
        ]);

        ServiceLog::create([
            'vehicle_id' => $vehicle2->id,
            'branch_id' => $branch1->id,
            'service_date' => now()->subDays(30),
            'odometer_reading' => 125000,
            'next_service_km' => 5000,
            'avg_monthly_km' => 3000,
            'service_type' => 'full_service',
            'cost' => 850000,
            'labor_cost' => 300000,
            'notes' => 'To\'liq servis',
        ]);

        // ============================================
        // 7. Chilonzor filiali uchun ma'lumotlar
        // ============================================

        // Mijozlar
        $client3 = Client::create([
            'workshop_id' => $workshop->id,
            'branch_id' => $branch2->id,
            'name' => 'Nodira Rahimova',
            'phone' => '+998903333333',
            'telegram_id' => null,
            'email' => 'nodira@yahoo.com',
            'notes' => 'Yangi mijoz',
        ]);

        $client4 = Client::create([
            'workshop_id' => $workshop->id,
            'branch_id' => $branch2->id,
            'name' => 'Sardor Aliyev',
            'phone' => '+998904444444',
            'telegram_id' => '555666777',
            'email' => null,
            'notes' => null,
        ]);

        // Avtomobillar
        $vehicle3 = Vehicle::create([
            'client_id' => $client3->id,
            'make' => 'Kia',
            'model' => 'Rio',
            'year' => 2019,
            'plate_number' => '01 D 321 HI',
            'vin' => 'Z94CB41AAKR345678',
        ]);

        $vehicle4 = Vehicle::create([
            'client_id' => $client4->id,
            'make' => 'Hyundai',
            'model' => 'Accent',
            'year' => 2021,
            'plate_number' => '01 E 654 JK',
            'vin' => 'KMHCT41BAAU901234',
        ]);

        // Servis yozuvlari
        ServiceLog::create([
            'vehicle_id' => $vehicle3->id,
            'branch_id' => $branch2->id,
            'service_date' => now()->subDays(5),
            'odometer_reading' => 62000,
            'next_service_km' => 5000,
            'avg_monthly_km' => 700,
            'service_type' => 'oil_change',
            'cost' => 420000,
            'labor_cost' => 120000,
            'notes' => 'Birinchi marta keldi',
        ]);

        ServiceLog::create([
            'vehicle_id' => $vehicle4->id,
            'branch_id' => $branch2->id,
            'service_date' => now()->subDays(15),
            'odometer_reading' => 28000,
            'next_service_km' => 5000,
            'avg_monthly_km' => 1200,
            'service_type' => 'inspection',
            'cost' => 650000,
            'labor_cost' => 200000,
            'notes' => 'Texnik ko\'rik',
        ]);

        // ============================================
        // 8. Xulosa
        // ============================================
        echo "\n✅ Test ma'lumotlar muvaffaqiyatli yaratildi!\n\n";
        echo "📊 Yaratilgan ma'lumotlar:\n";
        echo "   - Foydalanuvchilar: 6 ta\n";
        echo "   - Workshop: 1 ta\n";
        echo "   - Filiallar: 2 ta\n";
        echo "   - Kategoriyalar: 6 ta\n";
        echo "   - Mijozlar: 4 ta\n";
        echo "   - Avtomobillar: 4 ta\n";
        echo "   - Servis yozuvlari: 4 ta\n\n";

        echo "🔐 Login ma'lumotlari (telefon raqam orqali):\n\n";

        echo "   SuperAdmin (barcha tizimni ko'radi):\n";
        echo "     Telefon: +998937058855\n";
        echo "     Parol: password\n\n";

        echo "   Director (barcha filiallarni ko'radi):\n";
        echo "     Telefon: +998901234567\n";
        echo "     Parol: password\n\n";

        echo "   Manager Yunusobod (faqat Yunusobod filialini ko'radi):\n";
        echo "     Telefon: +998905555001\n";
        echo "     Parol: password\n\n";

        echo "   Employee Yunusobod (faqat Yunusobod filialini ko'radi):\n";
        echo "     Telefon: +998905555011\n";
        echo "     Parol: password\n\n";

        echo "   Manager Chilonzor (faqat Chilonzor filialini ko'radi):\n";
        echo "     Telefon: +998905555002\n";
        echo "     Parol: password\n\n";

        echo "   Employee Chilonzor (faqat Chilonzor filialini ko'radi):\n";
        echo "     Telefon: +998905555022\n";
        echo "     Parol: password\n\n";

        // ============================================
        // 9. Qo'shimcha demo workshop (moy/filtr mahsulotlari
        //    va avtomobil turlariga bog'lanishlari bilan)
        // ============================================
        $this->call(ProductSeeder::class);
    }
}
