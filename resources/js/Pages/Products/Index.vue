<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});

const filters = reactive({
    search: props.filters.search || '',
    category_id: props.filters.category_id || '',
    stock_status: props.filters.stock_status || '',
    sort_by: props.filters.sort_by || 'created_at',
    sort_dir: props.filters.sort_dir || 'desc',
    per_page: props.filters.per_page || 10,
});

const reload = () => {
    router.get(route('products.index'), { ...filters }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Qidiruv matnini debounce bilan yuborish, qolganlarini darhol
let searchTimeout = null;
watch(() => filters.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(reload, 400);
});
watch(() => [filters.category_id, filters.stock_status, filters.per_page], reload);

const resetFilters = () => {
    filters.search = '';
    filters.category_id = '';
    filters.stock_status = '';
    filters.sort_by = 'created_at';
    filters.sort_dir = 'desc';
    filters.per_page = 10;
    reload();
};

const sortColumns = [
    { key: 'name', label: 'Mahsulot' },
    { key: 'stock_quantity', label: 'Qoldiq' },
    { key: 'purchase_price', label: 'Tan narxi' },
    { key: 'selling_price', label: 'Sotuv narxi' },
];

const toggleSort = (key) => {
    if (filters.sort_by === key) {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc';
    } else {
        filters.sort_by = key;
        filters.sort_dir = 'asc';
    }
    reload();
};

const deleteProduct = (product) => {
    if (confirm(`Haqiqatan ham ${product.name} mahsulotini o'chirmoqchimisiz?`)) {
        router.delete(route('products.destroy', product.id));
    }
};

const formatMoney = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};
</script>

<template>
    <Head title="Mahsulotlar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Mahsulotlar
                </h2>
                <Link
                    :href="route('products.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi Mahsulot
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-4 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-4">
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="min-w-[12rem] flex-1">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Qidirish</label>
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    placeholder="Nomi yoki SKU bo'yicha..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Kategoriya</label>
                                <select
                                    v-model="filters.category_id"
                                    class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Barcha kategoriyalar</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Ombor holati</label>
                                <select
                                    v-model="filters.stock_status"
                                    class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Barchasi</option>
                                    <option value="out">Tugagan</option>
                                    <option value="low">Kam qolgan</option>
                                    <option value="in_stock">Yetarli</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Sahifada</label>
                                <select
                                    v-model="filters.per_page"
                                    class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                            </div>
                            <button
                                type="button"
                                @click="resetFilters"
                                class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                            >
                                Tozalash
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div v-if="products.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th
                                            v-for="col in sortColumns"
                                            :key="col.key"
                                            scope="col"
                                            class="cursor-pointer select-none px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                            @click="toggleSort(col.key)"
                                        >
                                            {{ col.label }}
                                            <span v-if="filters.sort_by === col.key" class="ml-0.5">
                                                {{ filters.sort_dir === 'asc' ? '▲' : '▼' }}
                                            </span>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Kategoriya
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Amallar
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="product in products.data" :key="product.id" :class="{'bg-red-50 dark:bg-red-900/20': product.stock_quantity <= product.min_stock_level}">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ product.name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ product.sku || '-' }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ product.stock_quantity }} {{ product.unit }}
                                            </div>
                                            <div v-if="product.stock_quantity <= product.min_stock_level" class="text-xs text-red-600 dark:text-red-400">
                                                Kam qolgan!
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                            {{ formatMoney(product.purchase_price) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                                            {{ formatMoney(product.selling_price) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span v-if="product.category" class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                {{ product.category.name }}
                                            </span>
                                            <span v-else class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Link
                                                :href="route('products.show', product.id)"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                Ko'rish
                                            </Link>
                                            <Link
                                                :href="route('products.adjust-stock', product.id)"
                                                class="ml-4 text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                            >
                                                Qoldiq
                                            </Link>
                                            <Link
                                                :href="route('products.edit', product.id)"
                                                class="ml-4 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                Tahrirlash
                                            </Link>
                                            <button
                                                @click="deleteProduct(product)"
                                                class="ml-4 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                O'chirish
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Jami {{ products.total }} tadan {{ products.from }}-{{ products.to }} ko'rsatilmoqda
                                </p>

                                <!-- Pagination -->
                                <div v-if="products.links.length > 3" class="flex justify-center">
                                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                        <Link
                                            v-for="(link, index) in products.links"
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
                        <div v-else class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400">Hech qanday mahsulot topilmadi</p>
                            <Link
                                :href="route('products.create')"
                                class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                            >
                                Birinchi mahsulotni qo'shing
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
