<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});

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
                        <form @submit.prevent="$inertia.get(route('products.index'), filters)" class="flex flex-wrap gap-4">
                            <div>
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    placeholder="Qidirish (nomi, SKU)"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                            </div>
                            <div>
                                <select
                                    v-model="filters.category_id"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option :value="null">Barcha kategoriyalar</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex items-center">
                                <input
                                    id="low_stock"
                                    v-model="filters.low_stock"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                />
                                <label for="low_stock" class="ml-2 text-sm text-gray-900 dark:text-gray-300">
                                    Faqat kam qolganlar
                                </label>
                            </div>
                            <button
                                type="submit"
                                class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-500"
                            >
                                Filtr
                            </button>
                            <Link
                                :href="route('products.index')"
                                class="rounded-md bg-gray-400 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-300"
                            >
                                Tozalash
                            </Link>
                        </form>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div v-if="products.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Mahsulot
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Kategoriya
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Qoldiq
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Tan narxi
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Sotuv narxi
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
                                            <span v-if="product.category" class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                {{ product.category.name }}
                                            </span>
                                            <span v-else class="text-sm text-gray-500 dark:text-gray-400">-</span>
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

                            <!-- Pagination -->
                            <div v-if="products.links.length > 3" class="mt-4 flex justify-center">
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
                        <div v-else class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400">Hali mahsulotlar yo'q</p>
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
