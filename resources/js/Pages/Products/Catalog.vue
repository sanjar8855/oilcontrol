<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    products: Object,
    categories: Array,
    copiedGlobalProductIds: Array,
    filters: Object,
});

const filters = reactive({
    search: props.filters.search || '',
    category_id: props.filters.category_id || '',
});

const reload = () => {
    router.get(route('products.catalog'), { ...filters }, {
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

const resetFilters = () => {
    filters.search = '';
    filters.category_id = '';
    reload();
};

const isCopied = (product) => props.copiedGlobalProductIds.includes(product.id);

// Har bir mahsulot uchun tanlash holati va narx/qoldiq qiymatlari
const selections = reactive({});
const getSelection = (product) => {
    if (!selections[product.id]) {
        selections[product.id] = reactive({
            selected: false,
            purchase_price: 0,
            selling_price: 0,
            stock_quantity: 0,
            min_stock_level: 0,
        });
    }
    return selections[product.id];
};

const selectedCount = computed(() => Object.values(selections).filter((s) => s.selected).length);

const form = useForm({ items: [] });

const submit = () => {
    form.transform(() => ({
        items: Object.entries(selections)
            .filter(([, s]) => s.selected)
            .map(([globalProductId, s]) => ({
                global_product_id: Number(globalProductId),
                purchase_price: s.purchase_price,
                selling_price: s.selling_price,
                stock_quantity: s.stock_quantity,
                min_stock_level: s.min_stock_level,
            })),
    })).post(route('products.copy-from-catalog'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Katalogdan mahsulot tanlash" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Katalogdan mahsulot tanlash
                </h2>
                <Link
                    :href="route('products.index')"
                    class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    &larr; Mahsulotlarga qaytish
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <div class="mb-4 rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Kerakli mahsulotlarni belgilang, so'ng har biri uchun o'zingizning tan narxi, sotuv narxi va boshlang'ich qoldig'ingizni kiriting. Kategoriya avtomatik qo'shiladi.
                    </p>
                </div>

                <div v-if="form.errors.error" class="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                    <p class="text-sm text-red-800 dark:text-red-200">{{ form.errors.error }}</p>
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

                <form @submit.prevent="submit">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-4">
                            <div v-if="products.data.length > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"></th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Mahsulot</th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kategoriya</th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tan narxi</th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sotuv narxi</th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Boshlang'ich qoldiq</th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Minimal qoldiq</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                        <tr v-for="product in products.data" :key="product.id" :class="{'opacity-50': isCopied(product)}">
                                            <td class="px-2 py-2">
                                                <input
                                                    v-if="!isCopied(product)"
                                                    type="checkbox"
                                                    v-model="getSelection(product).selected"
                                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                />
                                                <span v-else title="Allaqachon qo'shilgan" class="text-green-600 dark:text-green-400">&#10003;</span>
                                            </td>
                                            <td class="px-2 py-2">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ product.name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ product.sku || '-' }} / {{ product.unit }}</div>
                                            </td>
                                            <td class="whitespace-nowrap px-2 py-2">
                                                <span v-if="product.global_category" class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                    {{ product.global_category.name }}
                                                </span>
                                                <span v-else class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model="getSelection(product).purchase_price"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    :disabled="isCopied(product) || !getSelection(product).selected"
                                                    class="block w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:disabled:bg-gray-800"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model="getSelection(product).selling_price"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    :disabled="isCopied(product) || !getSelection(product).selected"
                                                    class="block w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:disabled:bg-gray-800"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model="getSelection(product).stock_quantity"
                                                    type="number"
                                                    min="0"
                                                    :disabled="isCopied(product) || !getSelection(product).selected"
                                                    class="block w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:disabled:bg-gray-800"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model="getSelection(product).min_stock_level"
                                                    type="number"
                                                    min="0"
                                                    :disabled="isCopied(product) || !getSelection(product).selected"
                                                    class="block w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:disabled:bg-gray-800"
                                                />
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
                            <div v-else class="py-12 text-center">
                                <p class="text-gray-500 dark:text-gray-400">Katalogda hali mahsulot yo'q</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="products.data.length > 0" class="sticky bottom-0 mt-4 flex items-center gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <button
                            type="submit"
                            :disabled="form.processing || selectedCount === 0"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                        >
                            Tanlanganlarni qo'shish ({{ selectedCount }} ta)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
