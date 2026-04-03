<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    salary: Object,
});

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

const totalAmount = computed(() => {
    return parseFloat(props.salary.amount) +
           parseFloat(props.salary.bonus || 0) -
           parseFloat(props.salary.deduction || 0);
});
</script>

<template>
    <Head title="Oylik maosh tafsilotlari" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Oylik maosh tafsilotlari</h2>
                <Link
                    :href="route('salaries.index')"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Orqaga
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Header Info -->
                    <div class="p-6 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                        <h3 class="text-2xl font-bold mb-2">{{ salary.user?.name }}</h3>
                        <p class="text-blue-100">{{ salary.user?.position }}</p>
                        <div class="mt-4 flex items-center gap-4">
                            <span class="bg-white dark:bg-gray-800 text-blue-600 px-4 py-1 rounded-full text-sm font-semibold">
                                {{ salary.month }}
                            </span>
                            <span class="text-blue-100">
                                To'langan: {{ formatDate(salary.payment_date) }}
                            </span>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Asosiy ma'lumotlar -->
                            <div class="space-y-4">
                                <h4 class="font-semibold text-lg text-gray-800 mb-4">Asosiy ma'lumotlar</h4>

                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">Xodim</label>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ salary.user?.name }}</p>
                                </div>

                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">Lavozim</label>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ salary.user?.position || '-' }}</p>
                                </div>

                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">Workshop</label>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ salary.workshop?.name || '-' }}</p>
                                </div>

                                <div v-if="salary.branch">
                                    <label class="text-sm text-gray-600 dark:text-gray-400">Filial</label>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ salary.branch?.name || '-' }}</p>
                                </div>
                            </div>

                            <!-- To'lov ma'lumotlari -->
                            <div class="space-y-4">
                                <h4 class="font-semibold text-lg text-gray-800 mb-4">To'lov ma'lumotlari</h4>

                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">To'lov usuli</label>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ getPaymentMethodLabel(salary.payment_method) }}</p>
                                </div>

                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">To'lagan shaxs</label>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ salary.paid_by?.name || '-' }}</p>
                                </div>

                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">To'lov sanasi</label>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(salary.payment_date) }}</p>
                                </div>

                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">Yaratilgan</label>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(salary.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Hisob-kitob -->
                        <div class="mt-8 p-6 bg-gray-50 rounded-lg">
                            <h4 class="font-semibold text-lg text-gray-800 mb-4">Hisob-kitob</h4>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 dark:text-gray-300">Asosiy maosh:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(salary.amount) }}</span>
                                </div>

                                <div v-if="salary.bonus > 0" class="flex justify-between items-center text-green-600">
                                    <span>Bonus:</span>
                                    <span class="font-medium">+{{ formatCurrency(salary.bonus) }}</span>
                                </div>

                                <div v-if="salary.deduction > 0" class="flex justify-between items-center text-red-600">
                                    <span>Ushlab qolish / Jarimalar:</span>
                                    <span class="font-medium">-{{ formatCurrency(salary.deduction) }}</span>
                                </div>

                                <div class="border-t-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white pt-3 mt-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">Jami to'langan:</span>
                                        <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(totalAmount) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Izoh -->
                        <div v-if="salary.notes" class="mt-6">
                            <h4 class="font-semibold text-lg text-gray-800 mb-2">Izoh</h4>
                            <p class="text-gray-700 bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
                                {{ salary.notes }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 pb-6 flex gap-4">
                        <Link
                            :href="route('salaries.index')"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded"
                        >
                            Ro'yxatga qaytish
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
