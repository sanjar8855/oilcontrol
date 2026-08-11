<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sinov muddati
    |--------------------------------------------------------------------------
    |
    | Yangi kompaniya ro'yxatdan o'tganda beriladigan bepul sinov kunlari.
    | Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 1.
    |
    */
    'trial_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Tarif rejalari
    |--------------------------------------------------------------------------
    |
    | Narx o'zgarsa shu yerda o'zgartiriladi — migratsiya kerak emas.
    | 'branches' / 'users' = null bo'lsa, cheklov yo'q (cheksiz).
    | Workshop'ga individual limit kerak bo'lsa workshops.limits_override
    | ustunidan shu qiymatlar ustidan yoziladi (Maxsus tarif uchun).
    |
    */
    'plans' => [
        'start' => [
            'name' => 'Start',
            'price' => 300000,
            'branches' => 1,
            'users' => 5,
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 600000,
            'branches' => 3,
            'users' => 20,
        ],
        'maxsus' => [
            'name' => 'Maxsus',
            'price' => null,
            'branches' => null,
            'users' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Obuna tugashidan oldin ogohlantirish (necha kun qolganda)
    |--------------------------------------------------------------------------
    */
    'warning_days' => [7, 3, 1],

];
