<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    product: Object,
});

const formatMoney = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uz-UZ');
};
</script>

<template>
    <Head :title="product.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ product.name }}
                </h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('products.adjust-stock', product.id)"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                    >
                        Qoldiq o'zgartirish
                    </Link>
                    <Link
                        :href="route('products.edit', product.id)"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        Tahrirlash
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-6">
                <!-- Product Details -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Mahsulot Ma'lumotlari</h3>
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategoriya</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ product.category?.name || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SKU</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ product.sku || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Barcode</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ product.barcode || '-' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">O'lchov birligi</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ product.unit }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tan narxi</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ formatMoney(product.purchase_price) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sotuv narxi</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ formatMoney(product.selling_price) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Joriy qoldiq</dt>
                                <dd class="mt-1 text-sm font-semibold" :class="product.stock_quantity <= product.min_stock_level ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                                    {{ product.stock_quantity }} {{ product.unit }}
                                    <span v-if="product.stock_quantity <= product.min_stock_level" class="text-xs"> (Kam qolgan!)</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Minimal qoldiq</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ product.min_stock_level }} {{ product.unit }}
                                </dd>
                            </div>
                            <div class="sm:col-span-2" v-if="product.description">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tavsifi</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ product.description }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Inventory Transactions -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Oxirgi Tranzaksiyalar</h3>
                        <div v-if="product.inventory_transactions && product.inventory_transactions.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sana</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Turi</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Miqdor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Narx</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sabab</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="transaction in product.inventory_transactions" :key="transaction.id">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                            {{ formatDate(transaction.transaction_date) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span v-if="transaction.type === 'in'" class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Kirim</span>
                                            <span v-else-if="transaction.type === 'out'" class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">Chiqim</span>
                                            <span v-else class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800">Tuzatish</span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <span :class="transaction.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                                                {{ transaction.quantity > 0 ? '+' : '' }}{{ transaction.quantity }} {{ product.unit }}
                                            </span>
                                            <div class="text-xs text-gray-500">
                                                {{ transaction.quantity_before }} → {{ transaction.quantity_after }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                            {{ formatMoney(transaction.total_price) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ transaction.reason || '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Hali tranzaksiyalar yo'q
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <Link
                        :href="route('products.index')"
                        class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                    >
                        ← Orqaga
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
