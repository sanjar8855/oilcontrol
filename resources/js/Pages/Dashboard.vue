<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

defineProps({
    workshop: Object,
    stats: Object,
    recent_clients: Array,
});

const formatMoney = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

// Qidiruv uchun
const searchPlateNumber = ref('');
const searching = ref(false);
const showAddClientModal = ref(false);

// Mijoz qo'shish form
const clientForm = useForm({
    name: '',
    phone: '',
    avg_daily_km: '',
    plate_number: '',
    make: '',
});

const searchVehicle = async () => {
    if (!searchPlateNumber.value.trim()) {
        alert('Iltimos, avto raqamni kiriting');
        return;
    }

    searching.value = true;

    try {
        const response = await axios.get(route('vehicles.search'), {
            params: {
                plate_number: searchPlateNumber.value.trim(),
            },
        });

        if (response.data.found) {
            // Topildi - vehicles.show sahifasiga o'tish
            router.visit(route('vehicles.show', response.data.vehicle.id));
        } else {
            // Topilmadi - mijoz qo'shish modal ochish
            clientForm.plate_number = searchPlateNumber.value.trim();
            showAddClientModal.value = true;
        }
    } catch (error) {
        console.error('Qidiruv xatosi:', error);
        alert('Qidiruv vaqtida xatolik yuz berdi');
    } finally {
        searching.value = false;
    }
};

const closeModal = () => {
    showAddClientModal.value = false;
    clientForm.reset();
};

const submitClient = () => {
    clientForm.post(route('clients.store-with-vehicle'), {
        onSuccess: () => {
            closeModal();
        },
        onError: (errors) => {
            console.error('Xatolik:', errors);
        },
    });
};
</script>

<template>
    <Head title="Boshqaruv Paneli" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Boshqaruv Paneli
                </h2>
                <Link
                    :href="route('clients.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi Mijoz
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Avto raqam qidiruv -->
                <div class="mb-6 overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                            Avtomobil qidirish
                        </h3>
                        <div class="flex gap-4">
                            <input
                                v-model="searchPlateNumber"
                                type="text"
                                placeholder="Avto raqamni kiriting (masalan: 01A123AA)"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                @keyup.enter="searchVehicle"
                            />
                            <button
                                @click="searchVehicle"
                                :disabled="searching"
                                class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                            >
                                {{ searching ? 'Qidirilmoqda...' : 'Qidirish' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistika Kartochkalari -->
                <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Jami mijozlar -->
                    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Jami mijozlar
                                        </dt>
                                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ stats.total_clients }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Obuna rejasi -->
                    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Obuna rejasi
                                        </dt>
                                        <dd class="text-lg font-semibold uppercase text-gray-900 dark:text-white">
                                            {{ stats.subscription_plan }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Qolgan kunlar -->
                    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Obuna qolgan kunlar
                                        </dt>
                                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ Math.floor(stats.days_remaining) }} kun
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ustaxona nomi -->
                    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Ustaxona
                                        </dt>
                                        <dd class="truncate text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ workshop.name }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Moliyaviy Statistika -->
                <div class="mb-6">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Moliyaviy Hisobotlar (Joriy oy)</h3>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Mahsulotlar -->
                        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Mahsulotlar
                                            </dt>
                                            <dd class="flex items-baseline text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ stats.total_products }}
                                                <span v-if="stats.low_stock_products > 0" class="ml-2 text-xs font-medium text-red-600 dark:text-red-400">
                                                    ({{ stats.low_stock_products }} kam qolgan)
                                                </span>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ombor qiymati -->
                        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Ombor qiymati
                                            </dt>
                                            <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ formatMoney(stats.inventory_value) }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Oylik daromad -->
                        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Oylik daromad
                                            </dt>
                                            <dd class="text-lg font-semibold text-green-600 dark:text-green-400">
                                                {{ formatMoney(stats.monthly_revenue) }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Oylik xarajatlar -->
                        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Oylik xarajatlar
                                            </dt>
                                            <dd class="text-lg font-semibold text-red-600 dark:text-red-400">
                                                {{ formatMoney(stats.monthly_expenses) }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Foyda/Zarar -->
                        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800 sm:col-span-2">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6" :class="stats.profit_loss >= 0 ? 'text-green-400' : 'text-red-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                {{ stats.profit_loss >= 0 ? 'Foyda' : 'Zarar' }}
                                            </dt>
                                            <dd class="text-lg font-semibold" :class="stats.profit_loss >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                                {{ formatMoney(Math.abs(stats.profit_loss)) }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Oxirgi mijozlar -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                Oxirgi qo'shilgan mijozlar
                            </h3>
                            <Link
                                :href="route('clients.index')"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                Barchasini ko'rish →
                            </Link>
                        </div>
                    </div>
                    <div class="p-6">
                        <div v-if="recent_clients.length > 0" class="space-y-4">
                            <div
                                v-for="client in recent_clients"
                                :key="client.id"
                                class="flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                            >
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                        {{ client.name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ client.phone }}
                                    </p>
                                </div>
                                <Link
                                    :href="route('clients.show', client.id)"
                                    class="rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                >
                                    Ko'rish
                                </Link>
                            </div>
                        </div>
                        <div v-else class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                Hali mijozlar yo'q
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Birinchi mijozingizni qo'shishdan boshlang
                            </p>
                            <div class="mt-6">
                                <Link
                                    :href="route('clients.create')"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                >
                                    + Mijoz qo'shish
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mijoz qo'shish Modal -->
        <Modal :show="showAddClientModal" @close="closeModal" max-width="2xl">
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    Yangi mijoz qo'shish
                </h2>
                <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                    Avto raqam <span class="font-semibold text-indigo-600">{{ clientForm.plate_number }}</span> topilmadi. Yangi mijoz qo'shishingiz mumkin.
                </p>

                <form @submit.prevent="submitClient">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Mijoz ismi -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Mijoz ismi <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="clientForm.name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <div v-if="clientForm.errors.name" class="mt-1 text-sm text-red-600">
                                {{ clientForm.errors.name }}
                            </div>
                        </div>

                        <!-- Telefon raqami -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Telefon raqami <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="phone"
                                v-model="clientForm.phone"
                                type="text"
                                required
                                placeholder="+998901234567"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <div v-if="clientForm.errors.phone" class="mt-1 text-sm text-red-600">
                                {{ clientForm.errors.phone }}
                            </div>
                        </div>

                        <!-- Kunlik km -->
                        <div>
                            <label for="avg_daily_km" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Kuniga taxminan necha km yuradi
                            </label>
                            <input
                                id="avg_daily_km"
                                v-model="clientForm.avg_daily_km"
                                type="number"
                                min="0"
                                placeholder="30"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <div v-if="clientForm.errors.avg_daily_km" class="mt-1 text-sm text-red-600">
                                {{ clientForm.errors.avg_daily_km }}
                            </div>
                        </div>

                        <!-- Mashina markasi -->
                        <div>
                            <label for="make" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Mashina markasi
                            </label>
                            <input
                                id="make"
                                v-model="clientForm.make"
                                type="text"
                                placeholder="Chevrolet Cobalt"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <div v-if="clientForm.errors.make" class="mt-1 text-sm text-red-600">
                                {{ clientForm.errors.make }}
                            </div>
                        </div>

                        <!-- Avto raqam (readonly) -->
                        <div class="sm:col-span-2">
                            <label for="plate_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Avto raqam <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="plate_number"
                                v-model="clientForm.plate_number"
                                type="text"
                                readonly
                                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm dark:border-gray-600 dark:bg-gray-600 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="clientForm.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {{ clientForm.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
