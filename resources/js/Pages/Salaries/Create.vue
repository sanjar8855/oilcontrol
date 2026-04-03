<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    employees: Array,
    workshops: Array,
});

const form = useForm({
    user_id: '',
    workshop_id: '',
    branch_id: null,
    amount: '',
    month: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    bonus: 0,
    deduction: 0,
    notes: '',
});

const selectedEmployee = ref(null);

// Xodim tanlanganda uning oylik maoshini avtomatik to'ldirish
watch(() => form.user_id, (newUserId) => {
    if (newUserId) {
        const employee = props.employees.find(e => e.id === parseInt(newUserId));
        if (employee) {
            selectedEmployee.value = employee;
            form.amount = employee.salary || '';
        }
    } else {
        selectedEmployee.value = null;
        form.amount = '';
    }
});

const calculateTotal = () => {
    const amount = parseFloat(form.amount) || 0;
    const bonus = parseFloat(form.bonus) || 0;
    const deduction = parseFloat(form.deduction) || 0;
    return amount + bonus - deduction;
};

const submit = () => {
    form.post(route('salaries.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Yangi oylik maosh to'lovi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yangi oylik maosh to'lovi</h2>
                <Link
                    :href="route('salaries.index')"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                >
                    Orqaga
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <!-- Xodim tanlash -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Xodim <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.user_id"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                                <option value="">Xodimni tanlang</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                    {{ employee.name }} - {{ employee.position }} ({{ new Intl.NumberFormat('uz-UZ').format(employee.salary) }} so'm)
                                </option>
                            </select>
                            <div v-if="form.errors.user_id" class="text-red-500 text-sm mt-1">
                                {{ form.errors.user_id }}
                            </div>
                        </div>

                        <!-- Workshop -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Workshop <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.workshop_id"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                                <option value="">Workshop tanlang</option>
                                <option v-for="workshop in workshops" :key="workshop.id" :value="workshop.id">
                                    {{ workshop.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.workshop_id" class="text-red-500 text-sm mt-1">
                                {{ form.errors.workshop_id }}
                            </div>
                        </div>

                        <!-- Summa -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                To'lov summasi (so'm) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            />
                            <div v-if="form.errors.amount" class="text-red-500 text-sm mt-1">
                                {{ form.errors.amount }}
                            </div>
                        </div>

                        <!-- Oy -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Oy (YYYY-MM) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.month"
                                type="month"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            />
                            <div v-if="form.errors.month" class="text-red-500 text-sm mt-1">
                                {{ form.errors.month }}
                            </div>
                        </div>

                        <!-- To'lov sanasi -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                To'lov sanasi <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.payment_date"
                                type="date"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            />
                            <div v-if="form.errors.payment_date" class="text-red-500 text-sm mt-1">
                                {{ form.errors.payment_date }}
                            </div>
                        </div>

                        <!-- To'lov usuli -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                To'lov usuli <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.payment_method"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                                <option value="cash">Naqd</option>
                                <option value="card">Karta</option>
                                <option value="transfer">O'tkazma</option>
                                <option value="other">Boshqa</option>
                            </select>
                            <div v-if="form.errors.payment_method" class="text-red-500 text-sm mt-1">
                                {{ form.errors.payment_method }}
                            </div>
                        </div>

                        <!-- Bonus -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Bonus (so'm)
                            </label>
                            <input
                                v-model="form.bonus"
                                type="number"
                                step="0.01"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <div v-if="form.errors.bonus" class="text-red-500 text-sm mt-1">
                                {{ form.errors.bonus }}
                            </div>
                        </div>

                        <!-- Chegirma -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Ushlab qolish / Jarimalar (so'm)
                            </label>
                            <input
                                v-model="form.deduction"
                                type="number"
                                step="0.01"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <div v-if="form.errors.deduction" class="text-red-500 text-sm mt-1">
                                {{ form.errors.deduction }}
                            </div>
                        </div>

                        <!-- Jami summa ko'rsatkichi -->
                        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                            <div class="text-sm text-gray-700 mb-2">Hisoblash:</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Asosiy: {{ new Intl.NumberFormat('uz-UZ').format(form.amount || 0) }} so'm
                            </div>
                            <div v-if="form.bonus > 0" class="text-sm text-green-600">
                                + Bonus: {{ new Intl.NumberFormat('uz-UZ').format(form.bonus) }} so'm
                            </div>
                            <div v-if="form.deduction > 0" class="text-sm text-red-600">
                                - Chegirma: {{ new Intl.NumberFormat('uz-UZ').format(form.deduction) }} so'm
                            </div>
                            <div class="text-lg font-bold text-gray-900 mt-2 border-t pt-2">
                                Jami: {{ new Intl.NumberFormat('uz-UZ').format(calculateTotal()) }} so'm
                            </div>
                        </div>

                        <!-- Izoh -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Izoh
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                            <div v-if="form.errors.notes" class="text-red-500 text-sm mt-1">
                                {{ form.errors.notes }}
                            </div>
                        </div>

                        <!-- Tugmalar -->
                        <div class="flex gap-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded disabled:opacity-50"
                            >
                                {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                            </button>
                            <Link
                                :href="route('salaries.index')"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded"
                            >
                                Bekor qilish
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
