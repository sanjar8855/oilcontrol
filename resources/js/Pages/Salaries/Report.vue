<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    employees: Array,
    filters: Object,
});

const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

const getTotalSalaries = (salaries) => {
    if (!salaries || salaries.length === 0) return 0;
    return salaries.reduce((sum, salary) => {
        return sum + parseFloat(salary.amount) + parseFloat(salary.bonus || 0) - parseFloat(salary.deduction || 0);
    }, 0);
};

const getTotalBonus = (salaries) => {
    if (!salaries || salaries.length === 0) return 0;
    return salaries.reduce((sum, salary) => sum + parseFloat(salary.bonus || 0), 0);
};

const getTotalDeduction = (salaries) => {
    if (!salaries || salaries.length === 0) return 0;
    return salaries.reduce((sum, salary) => sum + parseFloat(salary.deduction || 0), 0);
};

const applyFilters = () => {
    router.get(route('salaries.report'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    startDate.value = '';
    endDate.value = '';
    router.get(route('salaries.report'));
};

// Umumiy statistika
const grandTotal = ref(0);
const totalEmployees = ref(0);

const calculateGrandTotal = () => {
    grandTotal.value = props.employees.reduce((sum, employee) => {
        return sum + getTotalSalaries(employee.salaries);
    }, 0);
    totalEmployees.value = props.employees.length;
};

calculateGrandTotal();
</script>

<template>
    <Head title="Oylik maoshlar hisoboti" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Oylik maoshlar hisoboti</h2>
                <Link
                    :href="route('salaries.index')"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Ro'yxatga qaytish
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Boshlanish sanasi</label>
                            <input
                                v-model="startDate"
                                type="date"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tugash sanasi</label>
                            <input
                                v-model="endDate"
                                type="date"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
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

                <!-- Summary Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600 mb-1">Jami xodimlar</div>
                        <div class="text-3xl font-bold text-blue-600">{{ totalEmployees }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600 mb-1">Jami to'langan</div>
                        <div class="text-3xl font-bold text-green-600">{{ formatCurrency(grandTotal) }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600 mb-1">O'rtacha maosh</div>
                        <div class="text-3xl font-bold text-purple-600">
                            {{ formatCurrency(totalEmployees > 0 ? grandTotal / totalEmployees : 0) }}
                        </div>
                    </div>
                </div>

                <!-- Employee Reports -->
                <div class="space-y-6">
                    <div
                        v-for="employee in employees"
                        :key="employee.id"
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <!-- Employee Header -->
                        <div class="p-6 bg-gradient-to-r from-gray-50 to-gray-100 border-b">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">{{ employee.name }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ employee.position || 'Lavozim ko\'rsatilmagan' }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Asosiy maosh: {{ formatCurrency(employee.salary || 0) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-600">Jami to'langan</div>
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ formatCurrency(getTotalSalaries(employee.salaries)) }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ employee.salaries?.length || 0 }} ta to'lov
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Salary History -->
                        <div v-if="employee.salaries && employee.salaries.length > 0" class="p-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Oy</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Summa</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bonus</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Chegirma</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jami</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sana</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="salary in employee.salaries" :key="salary.id" class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ salary.month }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ formatCurrency(salary.amount) }}</td>
                                            <td class="px-4 py-3 text-sm text-green-600">
                                                {{ salary.bonus > 0 ? '+' + formatCurrency(salary.bonus) : '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-red-600">
                                                {{ salary.deduction > 0 ? '-' + formatCurrency(salary.deduction) : '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-bold text-gray-900">
                                                {{ formatCurrency(parseFloat(salary.amount) + parseFloat(salary.bonus || 0) - parseFloat(salary.deduction || 0)) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ formatDate(salary.payment_date) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-900">
                                                Jami:
                                            </td>
                                            <td colspan="2" class="px-4 py-3 text-sm font-bold text-blue-600">
                                                {{ formatCurrency(getTotalSalaries(employee.salaries)) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- No Salary History -->
                        <div v-else class="p-6 text-center text-gray-500">
                            Ushbu xodimga maosh to'lanmagan yoki filtr shartlariga mos to'lovlar topilmadi
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!employees || employees.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <p class="text-gray-500 text-lg">Xodimlar topilmadi</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
