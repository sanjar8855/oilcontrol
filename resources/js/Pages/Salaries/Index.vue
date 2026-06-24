<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    salaries: Object,
    filters: Object,
});

const searchMonth = ref(props.filters?.month || '');
const searchUserId = ref(props.filters?.user_id || '');

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

const getPaymentMethodLabel = (method) => {
    const labels = {
        cash: 'Naqd',
        card: 'Karta',
        transfer: 'O\'tkazma',
        other: 'Boshqa',
    };
    return labels[method] || method;
};

const getTotalAmount = (salary) => {
    return parseFloat(salary.amount) + parseFloat(salary.bonus || 0) - parseFloat(salary.deduction || 0);
};

const applyFilters = () => {
    router.get(route('salaries.index'), {
        month: searchMonth.value,
        user_id: searchUserId.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    searchMonth.value = '';
    searchUserId.value = '';
    router.get(route('salaries.index'));
};

const deleteSalary = (id) => {
    if (confirm('Ushbu oylik maosh yozuvini o\'chirmoqchimisiz?')) {
        router.delete(route('salaries.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Oylik maoshlar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Oylik maoshlar</h2>
                <Link
                    :href="route('salaries.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    + Yangi to'lov
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Oy (YYYY-MM)</label>
                            <input
                                v-model="searchMonth"
                                type="month"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div class="flex items-end gap-2">
                            <button
                                @click="applyFilters"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Qidirish
                            </button>
                            <button
                                @click="clearFilters"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Tozalash
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                    <Link
                        :href="route('salaries.report')"
                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-semibold"
                    >
                        📊 Hisobotlar sahifasi
                    </Link>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Xodim
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Oy
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Summa
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Bonus
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Chegirma
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Jami
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        To'lov sanasi
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Usul
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Amallar
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="salary in salaries.data" :key="salary.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ salary.user?.name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ salary.user?.position }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ salary.month }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency(salary.amount) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                                        {{ salary.bonus > 0 ? '+' + formatCurrency(salary.bonus) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400">
                                        {{ salary.deduction > 0 ? '-' + formatCurrency(salary.deduction) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency(getTotalAmount(salary)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatDate(salary.payment_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ getPaymentMethodLabel(salary.payment_method) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Link
                                            :href="route('salaries.show', salary.id)"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3"
                                        >
                                            Ko'rish
                                        </Link>
                                        <button
                                            @click="deleteSalary(salary.id)"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                        >
                                            O'chirish
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="salaries.links.length > 3" class="px-6 py-4 border-t dark:border-gray-700">
                        <div class="flex flex-wrap gap-1">
                            <Link
                                v-for="(link, index) in salaries.links"
                                :key="index"
                                :href="link.url"
                                :class="[
                                    'px-4 py-2 border rounded',
                                    link.active
                                        ? 'bg-blue-500 text-white border-blue-500'
                                        : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!salaries.data || salaries.data.length === 0" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        Ma'lumotlar topilmadi
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
