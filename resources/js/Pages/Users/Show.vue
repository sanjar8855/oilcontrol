<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user: Object,
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('uz-UZ');
};

const formatCurrency = (amount) => {
    if (!amount) return '-';
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

const getRoleLabel = (role) => {
    const labels = {
        superadmin: 'Super Admin',
        director: 'Direktor',
        manager: 'Menejer',
        employee: 'Xodim',
    };
    return labels[role] || role;
};

const getEmploymentStatusLabel = (status) => {
    const labels = {
        active: 'Faol',
        on_leave: 'Ta\'tilda',
        terminated: 'Ishdan bo\'shatilgan',
    };
    return labels[status] || status;
};

const getEmploymentStatusBadge = (status) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'on_leave':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'terminated':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const getTotalSalaryPaid = computed(() => {
    if (!props.user.salaries || props.user.salaries.length === 0) return 0;
    return props.user.salaries.reduce((sum, salary) => {
        return sum + parseFloat(salary.amount) + parseFloat(salary.bonus || 0) - parseFloat(salary.deduction || 0);
    }, 0);
});
</script>

<template>
    <Head :title="`Xodim - ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Xodim tafsilotlari
                </h2>
                <div class="flex gap-3">
                    <Link
                        :href="route('users.edit', user.id)"
                        class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Tahrirlash
                    </Link>
                    <Link
                        :href="route('users.index')"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Orqaga
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Asosiy ma'lumotlar -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Header with Avatar -->
                    <div class="p-6 bg-gradient-to-r from-indigo-500 to-purple-600 text-white">
                        <div class="flex items-center">
                            <div class="h-24 w-24 rounded-full bg-white dark:bg-gray-200 flex items-center justify-center">
                                <span class="text-indigo-700 font-bold text-4xl">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div class="ml-6">
                                <h3 class="text-3xl font-bold">{{ user.name }}</h3>
                                <p class="text-indigo-100 mt-1">{{ user.position || 'Lavozim ko\'rsatilmagan' }}</p>
                                <div class="mt-2 flex gap-3">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold bg-white text-indigo-700"
                                    >
                                        {{ getRoleLabel(user.role) }}
                                    </span>
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="getEmploymentStatusBadge(user.employment_status)"
                                    >
                                        {{ getEmploymentStatusLabel(user.employment_status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aloqa ma'lumotlari -->
                    <div class="p-6 border-b dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Aloqa ma'lumotlari</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="text-sm text-gray-600 dark:text-gray-400">Telefon raqam</label>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ user.phone || '-' }}</p>
                            </div>
                            <div v-if="user.phone_secondary">
                                <label class="text-sm text-gray-600 dark:text-gray-400">Qo'shimcha telefon</label>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ user.phone_secondary }}</p>
                            </div>
                            <div v-if="user.email">
                                <label class="text-sm text-gray-600 dark:text-gray-400">Email</label>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ user.email }}</p>
                            </div>
                            <div v-if="user.address">
                                <label class="text-sm text-gray-600 dark:text-gray-400">Manzil</label>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ user.address }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ish ma'lumotlari -->
                    <div class="p-6 border-b dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Ish ma'lumotlari</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="text-sm text-gray-600 dark:text-gray-400">Oylik maosh</label>
                                <p class="font-medium text-gray-900 dark:text-gray-100 text-xl">
                                    {{ formatCurrency(user.salary) }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600 dark:text-gray-400">Ishga qabul sanasi</label>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(user.hire_date) }}</p>
                            </div>
                            <div v-if="user.branch">
                                <label class="text-sm text-gray-600 dark:text-gray-400">Filial</label>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ user.branch.name }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Statistika -->
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Statistika</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                                <div class="text-sm text-blue-600 dark:text-blue-200">Jami to'langan maosh</div>
                                <div class="text-2xl font-bold text-blue-700 dark:text-blue-100 mt-1">
                                    {{ formatCurrency(getTotalSalaryPaid) }}
                                </div>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                                <div class="text-sm text-green-600 dark:text-green-200">To'lovlar soni</div>
                                <div class="text-2xl font-bold text-green-700 dark:text-green-100 mt-1">
                                    {{ user.salaries?.length || 0 }} ta
                                </div>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
                                <div class="text-sm text-purple-600 dark:text-purple-200">Ro'yxatdan o'tgan</div>
                                <div class="text-2xl font-bold text-purple-700 dark:text-purple-100 mt-1">
                                    {{ formatDate(user.created_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Oylik maoshlar tarixi -->
                <div v-if="user.salaries && user.salaries.length > 0" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Oylik maoshlar tarixi</h4>
                            <Link
                                :href="route('salaries.index') + '?user_id=' + user.id"
                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold"
                            >
                                Barchasini ko'rish →
                            </Link>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Oy</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Summa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bonus</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Chegirma</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Jami</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sana</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Amallar</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="salary in user.salaries.slice(0, 5)" :key="salary.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
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
                                        {{ formatCurrency(parseFloat(salary.amount) + parseFloat(salary.bonus || 0) - parseFloat(salary.deduction || 0)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatDate(salary.payment_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <Link
                                            :href="route('salaries.show', salary.id)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                        >
                                            Ko'rish
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty state for salaries -->
                <div v-else class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        Hali maosh to'lovlari yo'q
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Ushbu xodimga hali maosh to'lanmagan
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('salaries.create')"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                        >
                            + Maosh to'lovi qo'shish
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
