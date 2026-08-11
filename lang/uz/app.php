<?php

/*
 * Panel uchun tarjima kalitlari. Inertia shared props orqali Vue'ga uzatiladi
 * (HandleInertiaRequests::share() -> 'translations'). $t() composable orqali ishlatiladi.
 * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 4.
 */

return [
    'nav' => [
        'dashboard' => 'Boshqaruv',
        'clients' => 'Mijozlar',
        'vehicles' => 'Avtomobillar',
        'service_logs' => 'Servis Yozuvlari',
        'categories' => 'Kategoriyalar',
        'suppliers' => 'Ta\'minotchilar',
        'products' => 'Mahsulotlar',
        'inventories' => 'Inventarizatsiya',
        'expenses' => 'Xarajatlar',
        'reports' => 'Hisobotlar',
        'reminder_effectiveness' => 'Eslatma effektivligi',
        'branch_comparison' => 'Filiallarni solishtirish',
        'users' => 'Xodimlar',
        'branches' => 'Filiallar',
        'car_makes' => 'Avto markalari',
        'global_products' => 'Global katalog',
        'workshops' => 'Kompaniyalar',
        'profile' => 'Profil',
        'logout' => 'Chiqish',
        'workshop_not_selected' => 'Workshop tanlanmagan',
    ],

    'common' => [
        'save' => 'Saqlash',
        'saving' => 'Saqlanmoqda...',
        'cancel' => 'Bekor qilish',
        'delete' => 'O\'chirish',
        'edit' => 'O\'zgartirish',
        'back' => 'Orqaga',
        'view' => 'Ko\'rish',
        'search' => 'Qidirish',
        'searching' => 'Qidirilmoqda...',
        'actions' => 'Amallar',
        'yes' => 'Ha',
        'no' => 'Yo\'q',
        'view_all' => 'Barchasini ko\'rish →',
        'not_found' => 'Hech narsa topilmadi',
        'add' => 'Qo\'shish',
    ],

    'dashboard' => [
        'title' => 'Boshqaruv Paneli',
        'new_client' => '+ Yangi Mijoz',
        'vehicle_search_title' => 'Avtomobil qidirish',
        'vehicle_search_placeholder' => 'Avto raqam yoki telefon raqami (masalan: AA yoki 9012)',
        'no_plate' => 'Raqamsiz',
        'add_new_client' => '+ Yangi mijoz qo\'shish',
        'total_clients' => 'Jami mijozlar',
        'subscription_plan' => 'Obuna rejasi',
        'subscription_days_remaining' => 'Obuna qolgan kunlar',
        'days_suffix' => 'kun',
        'financial_reports_current_month' => 'Moliyaviy Hisobotlar (Joriy oy)',
        'products' => 'Mahsulotlar',
        'low_stock_suffix' => 'kam qolgan',
        'inventory_value' => 'Ombor qiymati',
        'monthly_revenue' => 'Oylik daromad',
        'monthly_expenses' => 'Oylik xarajatlar',
        'profit' => 'Foyda',
        'loss' => 'Zarar',
        'recent_clients' => 'Oxirgi qo\'shilgan mijozlar',
        'no_clients_yet' => 'Hali mijozlar yo\'q',
        'no_clients_hint' => 'Birinchi mijozingizni qo\'shishdan boshlang',
        'add_client_modal_title' => 'Yangi mijoz qo\'shish',
        'plate_not_found' => 'Avto raqam :plate topilmadi. Yangi mijoz qo\'shishingiz mumkin.',
        'client_name' => 'Mijoz ismi',
        'phone_number' => 'Telefon raqami',
        'avg_daily_km' => 'Kuniga taxminan necha km yuradi',
        'vehicle_make' => 'Mashina turi',
        'vehicle_make_placeholder' => 'Mashina turini tanlang',
        'plate_number' => 'Avto raqam',
    ],

    'subscription' => [
        'trial_ending' => 'Sinov muddati :days kundan so\'ng tugaydi.',
        'subscription_ending' => 'Obuna :days kundan so\'ng tugaydi.',
        'trial_expired' => 'Sinov muddati tugagan. Faqat ko\'rish va eksport qilish mumkin — davom etish uchun tarifni tanlang.',
        'subscription_expired' => 'Obuna muddati tugagan. Faqat ko\'rish va eksport qilish mumkin — yozish uchun to\'lovni yangilang.',
        'details' => 'Batafsil',
    ],

    'auth' => [
        'login' => 'Kirish',
        'phone' => 'Telefon raqam',
        'password' => 'Parol',
    ],
];
