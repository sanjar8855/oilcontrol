<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    expenses: Object,
    filters: Object,
    statistics: Object,
    categories: Array,
});

const deleteExpense = (expense) => {
    if (confirm(`Haqiqatan ham ${expense.title} xarajatini o'chirmoqchimisiz?`)) {
        router.delete(route('expenses.destroy', expense.id));
    }
};

const formatMoney = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uz-UZ');
};
</script>

<template>
    <Head title="Xarajatlar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Xarajatlar
                </h2>
                <Link
                    :href="route('expenses.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi Xarajat
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami Xarajat</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ formatMoney(statistics.total) }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-span-2 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Kategoriyalar bo'yicha</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="cat in statistics.by_category" :key="cat.category" class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ cat.category }}:</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatMoney(cat.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-4">
                        <form @submit.prevent="$inertia.get(route('expenses.index'), filters)" class="flex flex-wrap gap-4">
                            <div>
                                <select
                                    v-model="filters.category"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option :value="null">Barcha kategoriyalar</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.name">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <input
                                    v-model="filters.from_date"
                                    type="date"
                                    placeholder="Dan"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                            </div>
                            <div>
                                <input
                                    v-model="filters.to_date"
                                    type="date"
                                    placeholder="Gacha"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                            </div>
                            <button
                                type="submit"
                                class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-500"
                            >
                                Filtr
                            </button>
                            <Link
                                :href="route('expenses.index')"
                                class="rounded-md bg-gray-400 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-300"
                            >
                                Tozalash
                            </Link>
                        </form>
                    </div>
                </div>

                <!-- Expenses Table -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div v-if="expenses.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sana</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Xarajat</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kategoriya</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Summa</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">To'lov usuli</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Amallar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="expense in expenses.data" :key="expense.id">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                            {{ formatDate(expense.expense_date) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ expense.title }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ expense.description || '-' }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="inline-flex rounded-full bg-blue-100 px-2 text-xs font-semibold leading-5 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                {{ expense.category }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-red-600 dark:text-red-400">
                                            {{ formatMoney(expense.amount) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ expense.payment_method || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Link
                                                :href="route('expenses.edit', expense.id)"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                Tahrirlash
                                            </Link>
                                            <button
                                                @click="deleteExpense(expense)"
                                                class="ml-4 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                O'chirish
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div v-if="expenses.links.length > 3" class="mt-4 flex justify-center">
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                    <Link
                                        v-for="(link, index) in expenses.links"
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
                            <p class="text-gray-500 dark:text-gray-400">Hali xarajatlar yo'q</p>
                            <Link
                                :href="route('expenses.create')"
                                class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                            >
                                Birinchi xarajatni qo'shing
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
