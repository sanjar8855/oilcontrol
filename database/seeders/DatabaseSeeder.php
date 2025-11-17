<?php

namespace Database\Seeders;

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
        // 1. Test foydalanuvchi yaratish - Admin
        $user = User::create([
            'name' => 'Admin Adminov',
            'email' => 'admin@oilcontrol.uz',
            'password' => Hash::make('password'), // Parol: password
            'email_verified_at' => now(),
        ]);

        // 2. Workshop yaratish - Admin uchun
        $workshop = Workshop::create([
            'user_id' => $user->id,
            'name' => 'Avtomashina Servis Markazi',
            'owner_name' => 'Admin Adminov',
            'phone' => '+998901234567',
            'email' => 'info@avtomashina.uz',
            'address' => 'Toshkent shahar, Chilonzor tumani, Bunyodkor ko\'chasi 1-uy',
            'subscription_plan' => 'pro',
            'subscription_expires_at' => now()->addDays(365), // 1 yillik obuna
            'is_active' => true,
        ]);

        // Tadbirkor foydalanuvchi yaratish
        $entrepreneur = User::create([
            'name' => 'Sardor Toshmatov',
            'email' => 'tadbirkor@oilcontrol.uz',
            'password' => Hash::make('password'), // Parol: password
            'email_verified_at' => now(),
        ]);

        // Workshop yaratish - Tadbirkor uchun
        $entrepreneurWorkshop = Workshop::create([
            'user_id' => $entrepreneur->id,
            'name' => 'Universal Avto Servis',
            'owner_name' => 'Sardor Toshmatov',
            'phone' => '+998905555555',
            'email' => 'info@universal-avto.uz',
            'address' => 'Toshkent shahar, Yunusobod tumani, Amir Temur ko\'chasi 15-uy',
            'subscription_plan' => 'basic',
            'subscription_expires_at' => now()->addDays(180), // 6 oylik obuna
            'is_active' => true,
        ]);

        // 3. Mijozlar yaratish
        $client1 = Client::create([
            'workshop_id' => $workshop->id,
            'name' => 'Abbos Karimov',
            'phone' => '+998901111111',
            'telegram_id' => '123456789', // Test uchun
            'email' => 'abbos@gmail.com',
            'notes' => 'Doimiy mijoz, har 3 oyda keladi',
        ]);

        $client2 = Client::create([
            'workshop_id' => $workshop->id,
            'name' => 'Dilshod Tursunov',
            'phone' => '+998902222222',
            'telegram_id' => '987654321', // Test uchun
            'email' => 'dilshod@mail.ru',
            'notes' => 'Taksi haydovchisi, tez-tez servis kerak',
        ]);

        $client3 = Client::create([
            'workshop_id' => $workshop->id,
            'name' => 'Nodira Rahimova',
            'phone' => '+998903333333',
            'telegram_id' => null, // Telegram ID yo'q
            'email' => 'nodira@yahoo.com',
            'notes' => 'Yangi mijoz',
        ]);

        $client4 = Client::create([
            'workshop_id' => $workshop->id,
            'name' => 'Sardor Aliyev',
            'phone' => '+998904444444',
            'telegram_id' => '555666777',
            'email' => null,
            'notes' => null,
        ]);

        // 4. Avtomobillar yaratish
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

        $vehicle3 = Vehicle::create([
            'client_id' => $client2->id,
            'make' => 'Chevrolet',
            'model' => 'Spark',
            'year' => 2020,
            'plate_number' => '01 C 789 FG',
            'vin' => 'KL1CB6SA7LB789012',
        ]);

        $vehicle4 = Vehicle::create([
            'client_id' => $client3->id,
            'make' => 'Kia',
            'model' => 'Rio',
            'year' => 2019,
            'plate_number' => '01 D 321 HI',
            'vin' => 'Z94CB41AAKR345678',
        ]);

        $vehicle5 = Vehicle::create([
            'client_id' => $client4->id,
            'make' => 'Hyundai',
            'model' => 'Accent',
            'year' => 2021,
            'plate_number' => '01 E 654 JK',
            'vin' => 'KMHCT41BAAU901234',
        ]);

        // 5. Servis yozuvlari yaratish
        // Abbos - Lacetti (yaqinda servis qilingan)
        $service1 = ServiceLog::create([
            'vehicle_id' => $vehicle1->id,
            'service_date' => now()->subDays(10),
            'odometer_reading' => 85000,
            'next_service_km' => 5000,
            'avg_monthly_km' => 800,
            'service_type' => 'Yog\' almashtirish',
            'cost' => 450000,
            'notes' => 'Yog\' va filter almashtirildi. Hammasini tekshirildi.',
        ]);

        // Dilshod - Gentra (taksi, ko'p yuradi)
        $service2 = ServiceLog::create([
            'vehicle_id' => $vehicle2->id,
            'service_date' => now()->subDays(45),
            'odometer_reading' => 125000,
            'next_service_km' => 5000,
            'avg_monthly_km' => 3000, // Ko'p yuradi
            'service_type' => 'To\'liq servis',
            'cost' => 850000,
            'notes' => 'Yog\', filterlar, tormoz kolodkalari almashtirildi.',
        ]);

        // Dilshod - Spark (ikkinchi mashina)
        $service3 = ServiceLog::create([
            'vehicle_id' => $vehicle3->id,
            'service_date' => now()->subDays(60),
            'odometer_reading' => 45000,
            'next_service_km' => 5000,
            'avg_monthly_km' => 1000,
            'service_type' => 'Yog\' va filtr almashtirish',
            'cost' => 380000,
            'notes' => 'Muntazam servis',
        ]);

        // Nodira - Rio (yangi mijoz, birinchi servis)
        $service4 = ServiceLog::create([
            'vehicle_id' => $vehicle4->id,
            'service_date' => now()->subDays(5),
            'odometer_reading' => 62000,
            'next_service_km' => 5000,
            'avg_monthly_km' => 700,
            'service_type' => 'Yog\' almashtirish',
            'cost' => 420000,
            'notes' => 'Birinchi marta keldi',
        ]);

        // Sardor - Accent (yangi mashina, oldingi servis uzoq vaqt oldin)
        $service5 = ServiceLog::create([
            'vehicle_id' => $vehicle5->id,
            'service_date' => now()->subDays(120), // 4 oy oldin
            'odometer_reading' => 28000,
            'next_service_km' => 5000,
            'avg_monthly_km' => 1200,
            'service_type' => 'Texnik ko\'rik',
            'cost' => 650000,
            'notes' => 'Yangi mashina, garantiya servisi',
        ]);

        // 6. Test uchun ba'zi eslatmalar qo'lda yaratish
        // (Odatda ServiceLog yaratilganda avtomatik yaratiladi, lekin test uchun)

        // Yaqin kunda yuborilishi kerak bo'lgan eslatma
        Reminder::create([
            'service_log_id' => $service2->id,
            'scheduled_date' => now()->addDays(3), // 3 kundan keyin
            'status' => 'pending',
            'notification_type' => 'telegram',
            'message' => 'Servis vaqti yaqinlashmoqda!',
        ]);

        // Bugungi eslatma
        Reminder::create([
            'service_log_id' => $service5->id,
            'scheduled_date' => now(), // Bugun
            'status' => 'pending',
            'notification_type' => 'telegram',
            'message' => 'Bugun servis qilish vaqti!',
        ]);

        // O'tmishdagi eslatma (yuborilmagan)
        Reminder::create([
            'service_log_id' => $service3->id,
            'scheduled_date' => now()->subDays(2), // 2 kun oldin
            'status' => 'pending',
            'notification_type' => 'telegram',
            'message' => 'Servisga vaqti keldi',
        ]);

        // Yuborilgan eslatma (tarix)
        Reminder::create([
            'service_log_id' => $service1->id,
            'scheduled_date' => now()->subDays(15),
            'status' => 'sent',
            'sent_at' => now()->subDays(15),
            'notification_type' => 'telegram',
            'message' => 'Eslatma yuborilgan',
        ]);

        echo "\n✅ Test ma'lumotlar muvaffaqiyatli yaratildi!\n\n";
        echo "📊 Yaratilgan ma'lumotlar:\n";
        echo "   - Foydalanuvchilar: 2 ta\n";
        echo "   - Workshop: 2 ta\n";
        echo "   - Mijozlar: 4 ta\n";
        echo "   - Avtomobillar: 5 ta\n";
        echo "   - Servis yozuvlari: 5 ta\n";
        echo "   - Eslatmalar: 4 ta\n\n";
        echo "🔐 Login ma'lumotlari:\n";
        echo "   Admin:\n";
        echo "     Email: admin@oilcontrol.uz\n";
        echo "     Parol: password\n\n";
        echo "   Tadbirkor:\n";
        echo "     Email: tadbirkor@oilcontrol.uz\n";
        echo "     Parol: password\n\n";
    }
}
