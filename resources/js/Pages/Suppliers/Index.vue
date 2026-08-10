<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    suppliers: Object,
    filters: Object,
});

const filters = reactive({
    search: props.filters.search || '',
});

let searchTimeout = null;
watch(() => filters.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('suppliers.index'), { ...filters }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 400);
});

const deleteSupplier = (supplier) => {
    if (confirm(`Haqiqatan ham ${supplier.name} ta'minotchini o'chirmoqchimisiz?`)) {
        router.delete(route('suppliers.destroy', supplier.id));
    }
};

const formatMoney = (amount, currency) => {
    if (currency === 'USD') {
        return '$' + new Intl.NumberFormat('en-US').format(amount);
    }
    return new Intl.NumberFormat('uz-UZ').format(amount) + " so'm";
};

const hasDebt = (balances) => Object.values(balances || {}).some((v) => Math.abs(v) > 0.01);
</script>

<template>
    <Head title="Ta'minotchilar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Ta'minotchilar
                </h2>
                <Link
                    :href="route('suppliers.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi ta'minotchi
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <div class="mb-4 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-4">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Nomi yoki telefon bo'yicha qidirish..."
                            class="block w-full max-w-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                        />
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-4">
                        <div v-if="suppliers.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nomi</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Telefon</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Mahsulotlar</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Qarz</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Holati</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Amallar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="supplier in suppliers.data" :key="supplier.id">
                                        <td class="px-4 py-2">
                                            <Link :href="route('suppliers.show', supplier.id)" class="text-sm font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
                                                {{ supplier.name }}
                                            </Link>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            {{ supplier.phone || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-900 dark:text-gray-300">
                                            {{ supplier.products_count || 0 }} ta
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm">
                                            <div v-if="hasDebt(supplier.balances)" class="font-medium text-red-600 dark:text-red-400">
                                                <div v-for="(amount, currency) in supplier.balances" :key="currency">
                                                    <span v-if="Math.abs(amount) > 0.01">{{ formatMoney(amount, currency) }}</span>
                                                </div>
                                            </div>
                                            <span v-else class="text-gray-500 dark:text-gray-400">Qarzi yo'q</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2">
                                            <span v-if="supplier.is_active" class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                Faol
                                            </span>
                                            <span v-else class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Nofaol
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-medium">
                                            <Link :href="route('suppliers.show', supplier.id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                Ko'rish
                                            </Link>
                                            <Link :href="route('suppliers.edit', supplier.id)" class="ml-4 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                O'zgartirish
                                            </Link>
                                            <button @click="deleteSupplier(supplier)" class="ml-4 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                O'chirish
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Jami {{ suppliers.total }} tadan {{ suppliers.from }}-{{ suppliers.to }} ko'rsatilmoqda
                                </p>
                                <div v-if="suppliers.links.length > 3" class="flex justify-center">
                                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                        <Link
                                            v-for="(link, index) in suppliers.links"
                                            :key="index"
                                            :href="link.url"
                                            :class="[
                                                'relative inline-flex items-center px-4 py-2 text-sm font-semibold',
                                                link.active
                                                    ? 'z-10 bg-indigo-600 text-white'
                                                    : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700',
                                                !link.url && 'pointer-events-none opacity-50'
                                            ]"
                                            v-html="link.label"
                                        />
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center">
                            <p class="text-gray-500 dark:text-gray-400">Hech qanday ta'minotchi topilmadi</p>
                            <Link
                                :href="route('suppliers.create')"
                                class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                            >
                                Birinchi ta'minotchini qo'shing
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
