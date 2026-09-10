<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    products: Object,
    categories: Array,
    brands: Array,
    filters: Object,
});

const filters = reactive({
    search: props.filters.search || '',
    category_id: props.filters.category_id || '',
    brand_id: props.filters.brand_id || '',
});

const reload = () => {
    router.get(route('global-products.index'), { ...filters }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

let searchTimeout = null;
watch(() => filters.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(reload, 400);
});
watch(() => filters.category_id, reload);
watch(() => filters.brand_id, reload);

const resetFilters = () => {
    filters.search = '';
    filters.category_id = '';
    filters.brand_id = '';
    reload();
};

const deleteProduct = (product) => {
    if (confirm(`Haqiqatan ham "${product.name}" mahsulotini katalogdan o'chirmoqchimisiz?`)) {
        router.delete(route('global-products.destroy', product.id));
    }
};
</script>

<template>
    <Head title="Global mahsulotlar katalogi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Global mahsulotlar katalogi
                </h2>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('global-products.bulk-create')"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500"
                    >
                        Ommaviy kiritish
                    </Link>
                    <Link
                        :href="route('global-products.create')"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        + Yangi mahsulot
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <div class="mb-4 rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Bu yerdagi mahsulotlar barcha kompaniyalar uchun umumiy namuna sifatida ko'rinadi. Narx va qoldiq bo'lmaydi — ularni har bir tadbirkor katalogdan tanlab olganda o'zi kiritadi.
                    </p>
                </div>

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
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Brend</label>
                                <select
                                    v-model="filters.brand_id"
                                    class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Barcha brendlar</option>
                                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                        {{ brand.name }}
                                    </option>
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
                    <div class="p-4">
                        <div v-if="products.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Mahsulot</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kategoriya</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Birlik</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Amallar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="product in products.data" :key="product.id">
                                        <td class="px-4 py-1.5">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ product.name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ product.sku || '-' }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-1.5">
                                            <span v-if="product.global_category" class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                {{ product.global_category.name }}
                                            </span>
                                            <span v-if="product.brand" class="ml-1 inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                {{ product.brand.name }}
                                            </span>
                                            <span v-if="!product.global_category && !product.brand" class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-1.5 text-sm text-gray-900 dark:text-gray-300">
                                            {{ product.unit }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-1.5 text-right text-sm font-medium">
                                            <Link
                                                :href="route('global-products.edit', product.id)"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                O'zgartirish
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
                                :href="route('global-products.create')"
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
