<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';

const props = defineProps({
    filters: Object,
    summary: Object,
    paymentsByMethod: Object,
    topProducts: Array,
    salesByMake: Array,
    inventory: Object,
});

const range = reactive({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
});

// Mahalliy sana (UTC ga o'girmasdan) — toISOString() UTC ga o'tkazib,
// musbat vaqt zonalarida (masalan +5) bir kun orqaga surib yuborardi.
const toDateString = (date) => date.toLocaleDateString('sv-SE');

const applyRange = () => {
    router.get(route('reports.index'), { ...range }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const setPreset = (preset) => {
    const today = new Date();
    let from = new Date();
    const to = new Date();

    if (preset === 'today') {
        from = today;
    } else if (preset === 'week') {
        const day = today.getDay() === 0 ? 7 : today.getDay(); // dushanba = 1
        from.setDate(today.getDate() - (day - 1));
    } else if (preset === 'month') {
        from = new Date(today.getFullYear(), today.getMonth(), 1);
    }

    range.date_from = toDateString(from);
    range.date_to = toDateString(to);
    applyRange();
};

const activePreset = computed(() => {
    const today = toDateString(new Date());
    if (range.date_from === today && range.date_to === today) return 'today';

    const weekStart = new Date();
    const day = weekStart.getDay() === 0 ? 7 : weekStart.getDay();
    weekStart.setDate(weekStart.getDate() - (day - 1));
    if (range.date_from === toDateString(weekStart) && range.date_to === today) return 'week';

    const monthStart = new Date();
    monthStart.setDate(1);
    if (range.date_from === toDateString(monthStart) && range.date_to === today) return 'month';

    return 'custom';
});

const formatMoney = (amount) => new Intl.NumberFormat('uz-UZ').format(Math.round(amount || 0)) + " so'm";
</script>

<template>
    <Head title="Hisobotlar" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Hisobotlar
            </h2>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-6">
                <!-- Sana filtri -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex flex-wrap items-end gap-4 p-4">
                        <div class="flex gap-2">
                            <button
                                v-for="preset in [{ key: 'today', label: 'Bugun' }, { key: 'week', label: 'Aktiv hafta' }, { key: 'month', label: 'Aktiv oy' }]"
                                :key="preset.key"
                                type="button"
                                @click="setPreset(preset.key)"
                                :class="[
                                    'rounded-md px-3 py-2 text-sm font-semibold',
                                    activePreset === preset.key
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600',
                                ]"
                            >
                                {{ preset.label }}
                            </button>
                        </div>

                        <div class="flex items-end gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Dan</label>
                                <input
                                    v-model="range.date_from"
                                    type="date"
                                    class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Gacha</label>
                                <input
                                    v-model="range.date_to"
                                    type="date"
                                    class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                            </div>
                            <button
                                type="button"
                                @click="applyRange"
                                class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-500"
                            >
                                Ko'rsatish
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Asosiy statistika -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Savdolar soni</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ summary.sales_count }}</p>
                        </div>
                    </div>
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Savdo (jami)</p>
                            <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatMoney(summary.revenue) }}</p>
                        </div>
                    </div>
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Xarajatlar</p>
                            <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">{{ formatMoney(summary.expenses) }}</p>
                        </div>
                    </div>
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Sof foyda</p>
                            <p
                                class="mt-2 text-2xl font-bold"
                                :class="summary.net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                            >
                                {{ formatMoney(summary.net_profit) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- To'lov turlari -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">To'lov turlari bo'yicha</h3>
                    </div>
                    <div class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0 dark:divide-gray-700">
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">💵 Naqd</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ formatMoney(paymentsByMethod.cash) }}</p>
                        </div>
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">📲 Click</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ formatMoney(paymentsByMethod.click) }}</p>
                        </div>
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">🧾 Nasiyaga berilgan</p>
                            <p class="mt-1 text-xl font-bold text-amber-600 dark:text-amber-400">{{ formatMoney(summary.credit_extended) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Ombor holati -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Ombor holati</h3>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-y divide-gray-100 sm:grid-cols-4 sm:divide-y-0 dark:divide-gray-700">
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami mahsulot turi</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ inventory.total_products }}</p>
                        </div>
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Ombor qiymati</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ formatMoney(inventory.total_value) }}</p>
                        </div>
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Kam qolgan</p>
                            <p class="mt-1 text-xl font-bold text-amber-600 dark:text-amber-400">{{ inventory.low_stock_count }}</p>
                        </div>
                        <div class="p-5">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tugagan</p>
                            <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">{{ inventory.out_of_stock_count }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Eng ko'p sotilgan mahsulotlar -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Eng ko'p sotilgan mahsulotlar</h3>
                        </div>
                        <div v-if="topProducts.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Mahsulot</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Miqdor</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Savdo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="product in topProducts" :key="product.id">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ product.name }}</td>
                                        <td class="px-4 py-2 text-right text-sm text-gray-600 dark:text-gray-300">
                                            {{ Number(product.total_quantity) }} {{ product.unit }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatMoney(product.total_revenue) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="p-5 text-center text-sm text-gray-400 dark:text-gray-500">
                            Bu davrda savdo bo'lmagan
                        </p>
                    </div>

                    <!-- Avtomobil markalari bo'yicha savdo -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Avtomobil markalari bo'yicha savdo</h3>
                        </div>
                        <div v-if="salesByMake.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Marka</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Servislar</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Savdo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="row in salesByMake" :key="row.make">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ row.make }}</td>
                                        <td class="px-4 py-2 text-right text-sm text-gray-600 dark:text-gray-300">{{ row.services_count }}</td>
                                        <td class="px-4 py-2 text-right text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatMoney(row.total_revenue) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="p-5 text-center text-sm text-gray-400 dark:text-gray-500">
                            Bu davrda savdo bo'lmagan
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
