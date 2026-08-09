<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="OilControl - Moy Almashtirish Tizimi" />

    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-black to-gray-900">
        <!-- Header -->
        <header class="fixed w-full top-0 z-50 bg-gray-900/80 backdrop-blur-lg border-b border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-white">OilControl</span>
                    </div>

                    <!-- Navigation -->
                    <nav v-if="canLogin" class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-cyan-500 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-cyan-600 transition duration-300"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="px-4 py-2 text-gray-300 hover:text-white transition duration-300"
                            >
                                Kirish
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="px-6 py-2 bg-gradient-to-r from-blue-600 to-cyan-500 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-cyan-600 transition duration-300"
                            >
                                Bepul Boshlash
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="text-center">
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold text-white mb-6 leading-tight">
                        Moy Almashtirish Biznesingizni
                        <span class="bg-gradient-to-r from-blue-400 to-cyan-400 text-transparent bg-clip-text">
                            Raqamlashtiring
                        </span>
                    </h1>
                    <p class="text-xl sm:text-2xl text-gray-300 mb-8 max-w-3xl mx-auto">
                        Inventarizatsiya, sotuv, mijozlar bilan ishlash, telegram bot va vaqt bron qilish - hammasi bir tizimda
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-500 text-white text-lg font-bold rounded-xl hover:from-blue-700 hover:to-cyan-600 transition duration-300 shadow-2xl shadow-blue-500/50"
                        >
                            30 Kun Bepul Sinab Ko'ring
                        </Link>
                        <a
                            href="#features"
                            class="px-8 py-4 bg-gray-800 text-white text-lg font-semibold rounded-xl hover:bg-gray-700 transition duration-300 border border-gray-700"
                        >
                            Ko'proq Ma'lumot
                        </a>
                    </div>
                    <p class="mt-6 text-sm text-gray-400">
                        ✓ Kredit karta kerak emas &nbsp;&nbsp; ✓ Bir daqiqada sozlash &nbsp;&nbsp; ✓ Istalgan vaqt bekor qilish
                    </p>
                </div>

                <!-- Stats -->
                <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                    <div class="bg-gray-800/50 backdrop-blur-lg rounded-2xl p-6 border border-gray-700">
                        <div class="text-4xl font-bold text-blue-400 mb-2">100+</div>
                        <div class="text-gray-300">Faol Foydalanuvchilar</div>
                    </div>
                    <div class="bg-gray-800/50 backdrop-blur-lg rounded-2xl p-6 border border-gray-700">
                        <div class="text-4xl font-bold text-cyan-400 mb-2">10,000+</div>
                        <div class="text-gray-300">Kunlik Operatsiyalar</div>
                    </div>
                    <div class="bg-gray-800/50 backdrop-blur-lg rounded-2xl p-6 border border-gray-700">
                        <div class="text-4xl font-bold text-green-400 mb-2">99.9%</div>
                        <div class="text-gray-300">Tizim Barqarorligi</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-900/50">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl sm:text-5xl font-bold text-white mb-4">
                        Kuchli Imkoniyatlar
                    </h2>
                    <p class="text-xl text-gray-400">
                        Biznesingizni boshqarish uchun barcha kerakli vositalar
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-8 rounded-2xl border border-gray-700 hover:border-blue-500 transition duration-300">
                        <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Inventarizatsiya (FIFO)</h3>
                        <p class="text-gray-400">
                            Mahsulotlar narxini avtomatik hisoblash, multi-valyuta ($ va so'm), kam qolgan mahsulotlar ogohlantirish
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-8 rounded-2xl border border-gray-700 hover:border-cyan-500 transition duration-300">
                        <div class="w-14 h-14 bg-cyan-500/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Sotuv va To'lovlar</h3>
                        <p class="text-gray-400">
                            Naqd, nasiya, qarz to'lovlari. Select2 bilan tez qidiruv. Realizatsiya tizimi bilan dilerlar bilan ishlash
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-8 rounded-2xl border border-gray-700 hover:border-green-500 transition duration-300">
                        <div class="w-14 h-14 bg-green-500/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Mijozlar Boshqaruvi</h3>
                        <p class="text-gray-400">
                            Mijozlar bazasi, avtomobillar tarixi, moy almashtirish grafigi, keyingi tashrif vaqtini eslatma
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-8 rounded-2xl border border-gray-700 hover:border-purple-500 transition duration-300">
                        <div class="w-14 h-14 bg-purple-500/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Hisobotlar va Tahlil</h3>
                        <p class="text-gray-400">
                            Mahsulotlar kesimida, tushum kesimida, mijozlar bo'yicha batafsil hisobotlar va statistika
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-8 rounded-2xl border border-gray-700 hover:border-blue-500 transition duration-300">
                        <div class="w-14 h-14 bg-blue-500/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Telegram Bot</h3>
                        <p class="text-gray-400">
                            Mijozlarga avtomatik xabarlar, sotuv ma'lumotlari, keyingi tashrif eslatmalari telegram orqali
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-8 rounded-2xl border border-gray-700 hover:border-yellow-500 transition duration-300">
                        <div class="w-14 h-14 bg-yellow-500/20 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Vaqt Bron Qilish</h3>
                        <p class="text-gray-400">
                            Mijozlar telegram bot orqali vaqt bron qilishi, navbat tizimi, kalendar boshqaruvi
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl sm:text-5xl font-bold text-white mb-4">
                        Oddiy va Shaffof Narxlar
                    </h2>
                    <p class="text-xl text-gray-400">
                        Biznesingiz o'lchamiga mos tarif rejasini tanlang
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <!-- Free Plan -->
                    <div class="bg-gray-800 p-8 rounded-2xl border border-gray-700">
                        <h3 class="text-2xl font-bold text-white mb-2">Bepul</h3>
                        <p class="text-gray-400 mb-6">Yangi boshlovchilar uchun</p>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-white">$0</span>
                            <span class="text-gray-400">/oy</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-300">30 kunlik bepul sinov</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-300">100 tagacha mahsulot</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-300">Asosiy hisobotlar</span>
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="block w-full px-6 py-3 bg-gray-700 text-white text-center rounded-lg font-semibold hover:bg-gray-600 transition duration-300"
                        >
                            Boshlash
                        </Link>
                    </div>

                    <!-- Pro Plan -->
                    <div class="bg-gradient-to-br from-blue-600 to-cyan-600 p-8 rounded-2xl border-4 border-blue-400 relative transform scale-105">
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-900 px-4 py-1 rounded-full text-sm font-bold">
                            OMMABOP
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2">Professional</h3>
                        <p class="text-blue-100 mb-6">Kichik va o'rta biznes uchun</p>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-white">500 000</span>
                            <span class="text-blue-100">so'm/oy</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-white">Cheksiz mahsulotlar</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-white">Barcha hisobotlar</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-white">Telegram bot integratsiyasi</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-white">Prioritet qo'llab-quvvatlash</span>
                            </li>
                        </ul>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="block w-full px-6 py-3 bg-white text-blue-600 text-center rounded-lg font-bold hover:bg-gray-100 transition duration-300"
                        >
                            Hoziroq Boshlash
                        </Link>
                    </div>

                    <!-- Enterprise Plan -->
                    <div class="bg-gray-800 p-8 rounded-2xl border border-gray-700">
                        <h3 class="text-2xl font-bold text-white mb-2">Maxsus</h3>
                        <p class="text-gray-400 mb-6">Katta biznes uchun</p>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-white">Aloqa</span>
                        </div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-300">Professional rejadagi hammasi</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-300">Maxsus funksiyalar</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-300">API kirish</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-300">24/7 qo'llab-quvvatlash</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-300">Shaxsiy menejer</span>
                            </li>
                        </ul>
                        <a
                            href="tel:+998901234567"
                            class="block w-full px-6 py-3 bg-gray-700 text-white text-center rounded-lg font-semibold hover:bg-gray-600 transition duration-300"
                        >
                            Aloqa
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-blue-600 to-cyan-600">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-4xl sm:text-5xl font-bold text-white mb-6">
                    Bugun Boshlab Ko'ring
                </h2>
                <p class="text-xl text-blue-100 mb-8">
                    30 kun bepul sinab ko'ring. Istalgan vaqt bekor qilish.
                </p>
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="inline-block px-8 py-4 bg-white text-blue-600 text-lg font-bold rounded-xl hover:bg-gray-100 transition duration-300 shadow-2xl"
                >
                    Bepul Boshlash
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 border-t border-gray-800 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                    <!-- Company Info -->
                    <div>
                        <div class="flex items-center space-x-2 mb-4">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-white">OilControl</span>
                        </div>
                        <p class="text-gray-400 text-sm">
                            Moy almashtirish biznesini boshqarish uchun professional tizim
                        </p>
                    </div>

                    <!-- Product -->
                    <div>
                        <h3 class="text-white font-semibold mb-4">Mahsulot</h3>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#features" class="text-gray-400 hover:text-white transition">Imkoniyatlar</a></li>
                            <li><a href="#pricing" class="text-gray-400 hover:text-white transition">Narxlar</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">Demo</a></li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <div>
                        <h3 class="text-white font-semibold mb-4">Qo'llab-quvvatlash</h3>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#" class="text-gray-400 hover:text-white transition">Yordam Markazi</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">Bog'lanish</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">FAQ</a></li>
                        </ul>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h3 class="text-white font-semibold mb-4">Aloqa</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="text-gray-400">Email: info@oilcontrol.uz</li>
                            <li class="text-gray-400">Tel: +998 90 123 45 67</li>
                            <li class="text-gray-400">Toshkent, O'zbekiston</li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-800 pt-8 text-center">
                    <p class="text-gray-400 text-sm">
                        &copy; 2024 OilControl. Barcha huquqlar himoyalangan.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
