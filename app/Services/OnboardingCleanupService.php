<?php

namespace App\Services;

use App\Models\Workshop;

class OnboardingCleanupService
{
    /**
     * Onboarding paytida (1-3 qadamlarda) yaratilgan sinov ma'lumotlarini
     * butunlay o'chiradi — mijoz/mashina/servis/eslatma/to'lov/ombor
     * harakati kabi bog'liq yozuvlar DB'dagi cascade FK'lar orqali birga
     * o'chadi. Onboarding hali tugallanmagan workshopda shu sinov
     * yozuvlaridan boshqa haqiqiy ma'lumot bo'lishi mumkin emas —
     * EnsureOnboardingComplete middleware boshqa har qanday sahifaga
     * kirishni to'sib turadi, shuning uchun workshopga tegishli
     * clients/products/expenses'ni butunlay tozalash xavfsiz.
     */
    public function purge(Workshop $workshop): void
    {
        $workshop->clients()->delete();
        $workshop->products()->delete();
        $workshop->expenses()->delete();
    }
}
