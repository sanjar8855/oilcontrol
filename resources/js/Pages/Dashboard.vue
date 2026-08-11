<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';

defineProps({
    workshop: Object,
    stats: Object,
    recent_clients: Array,
    carMakeGroups: Array,
});

const formatMoney = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

// Qidiruv uchun (avto raqam yoki telefon raqami bo'yicha, qisman moslik)
const searchQuery = ref('');
const searching = ref(false);
const searchResults = ref([]);
const hasSearched = ref(false);
const showAddClientModal = ref(false);

// Mijoz qo'shish form
const clientForm = useForm({
    name: '',
    phone: '',
    avg_daily_km: '',
    plate_number: '',
    make: '',
});

const runSearch = async (query) => {
    const term = query.trim();

    if (term.length < 2) {
        searchResults.value = [];
        hasSearched.value = false;
        return;
    }

    searching.value = true;

    try {
        const response = await axios.get(route('vehicles.search'), {
            params: { query: term },
        });

        searchResults.value = response.data.vehicles;
        hasSearched.value = true;
    } catch (error) {
        console.error('Qidiruv xatosi:', error);
    } finally {
        searching.value = false;
    }
};

let searchDebounceTimer = null;

watch(searchQuery, (value) => {
    clearTimeout(searchDebounceTimer);

    if (!value.trim()) {
        searchResults.value = [];
        hasSearched.value = false;
        return;
    }

    searchDebounceTimer = setTimeout(() => {
        runSearch(value);
    }, 1000);
});

const openVehicle = (vehicleId) => {
    router.visit(route('vehicles.show', vehicleId));
};

const openAddClientModal = () => {
    // Agar qidiruv matni avto raqamga o'xshasa, formaga oldindan qo'yamiz
    clientForm.plate_number = searchQuery.value.trim();
    showAddClientModal.value = true;
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
    <Head :title="$t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ $t('dashboard.title') }}
                </h2>
                <Link
                    :href="route('clients.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    {{ $t('dashboard.new_client') }}
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <!-- Avto raqam / telefon raqam qidiruv -->
                <div class="relative mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="p-4 sm:p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $t('dashboard.vehicle_search_title') }}
                        </h3>
                        <div class="flex gap-4">
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="$t('dashboard.vehicle_search_placeholder')"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                @keyup.enter="clearTimeout(searchDebounceTimer); runSearch(searchQuery)"
                            />
                            <span v-if="searching" class="self-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('common.searching') }}
                            </span>
                        </div>

                        <!-- Natijalar -->
                        <div v-if="hasSearched && !searching" class="mt-3">
                            <div v-if="searchResults.length > 0" class="divide-y divide-gray-100 rounded-md border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                                <button
                                    v-for="vehicle in searchResults"
                                    :key="vehicle.id"
                                    type="button"
                                    @click="openVehicle(vehicle.id)"
                                    class="flex w-full items-center justify-between p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ vehicle.plate_number || $t('dashboard.no_plate') }}
                                        </span>
                                        <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                            {{ vehicle.make }} {{ vehicle.model }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ vehicle.client?.name }} · {{ vehicle.client?.phone }}
                                    </div>
                                </button>
                            </div>
                            <div v-else class="flex items-center justify-between rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $t('common.not_found') }}</span>
                                <button
                                    type="button"
                                    @click="openAddClientModal"
                                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500"
                                >
                                    {{ $t('dashboard.add_new_client') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistika Kartochkalari -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                    <!-- Jami mijozlar -->
                    <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="p-4 sm:p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ $t('dashboard.total_clients') }}
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
                        <div class="p-4 sm:p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ $t('dashboard.subscription_plan') }}
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
                        <div class="p-4 sm:p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ $t('dashboard.subscription_days_remaining') }}
                                        </dt>
                                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ Math.floor(stats.days_remaining) }} {{ $t('dashboard.days_suffix') }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Moliyaviy Statistika -->
                <div class="mb-6">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ $t('dashboard.financial_reports_current_month') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3">
                        <!-- Mahsulotlar -->
                        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                            <div class="p-4 sm:p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('dashboard.products') }}
                                            </dt>
                                            <dd class="flex items-baseline text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ stats.total_products }}
                                                <span v-if="stats.low_stock_products > 0" class="ml-2 text-xs font-medium text-red-600 dark:text-red-400">
                                                    ({{ stats.low_stock_products }} {{ $t('dashboard.low_stock_suffix') }})
                                                </span>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ombor qiymati -->
                        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                            <div class="p-4 sm:p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('dashboard.inventory_value') }}
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
                            <div class="p-4 sm:p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('dashboard.monthly_revenue') }}
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
                            <div class="p-4 sm:p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('dashboard.monthly_expenses') }}
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
                            <div class="p-4 sm:p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6" :class="stats.profit_loss >= 0 ? 'text-green-400' : 'text-red-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                                {{ stats.profit_loss >= 0 ? $t('dashboard.profit') : $t('dashboard.loss') }}
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
                                {{ $t('dashboard.recent_clients') }}
                            </h3>
                            <Link
                                :href="route('clients.index')"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                {{ $t('common.view_all') }}
                            </Link>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6">
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
                                    {{ $t('common.view') }}
                                </Link>
                            </div>
                        </div>
                        <div v-else class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $t('dashboard.no_clients_yet') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('dashboard.no_clients_hint') }}
                            </p>
                            <div class="mt-6">
                                <Link
                                    :href="route('clients.create')"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                >
                                    {{ $t('dashboard.new_client') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mijoz qo'shish Modal -->
        <Modal :show="showAddClientModal" @close="closeModal" max-width="2xl">
            <div class="p-4 sm:p-6">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $t('dashboard.add_client_modal_title') }}
                </h2>
                <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('dashboard.plate_not_found', { plate: clientForm.plate_number }) }}
                </p>

                <form @submit.prevent="submitClient">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Mijoz ismi -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('dashboard.client_name') }} <span class="text-red-500">*</span>
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
                                {{ $t('dashboard.phone_number') }} <span class="text-red-500">*</span>
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
                                {{ $t('dashboard.avg_daily_km') }}
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

                        <!-- Mashina turi -->
                        <div>
                            <label for="make" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('dashboard.vehicle_make') }}
                            </label>
                            <Multiselect
                                id="make"
                                v-model="clientForm.make"
                                :options="carMakeGroups"
                                :groups="true"
                                :searchable="true"
                                :placeholder="$t('dashboard.vehicle_make_placeholder')"
                                noOptionsText="Topilmadi"
                                noResultsText="Natija topilmadi"
                                class="mt-1"
                            />
                            <div v-if="clientForm.errors.make" class="mt-1 text-sm text-red-600">
                                {{ clientForm.errors.make }}
                            </div>
                        </div>

                        <!-- Avto raqam -->
                        <div class="sm:col-span-2">
                            <label for="plate_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('dashboard.plate_number') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="plate_number"
                                v-model="clientForm.plate_number"
                                type="text"
                                required
                                placeholder="01A123AA"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <div v-if="clientForm.errors.plate_number" class="mt-1 text-sm text-red-600">
                                {{ clientForm.errors.plate_number }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="clientForm.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {{ clientForm.processing ? $t('common.saving') : $t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
