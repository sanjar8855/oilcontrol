<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
});

const form = useForm({
    category: props.categories.length > 0 ? props.categories[0].name : '',
    title: '',
    description: '',
    amount: 0,
    expense_date: new Date().toLocaleDateString('sv-SE'),
    payment_method: null,
    receipt_number: '',
});

const submit = () => {
    form.post(route('expenses.store'));
};
</script>

<template>
    <Head title="Yangi Xarajat" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Yangi Xarajat
            </h2>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-3xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Category -->
                                <div>
                                    <InputLabel for="category" value="Kategoriya *" />
                                    <select
                                        id="category"
                                        v-model="form.category"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        required
                                    >
                                        <option v-for="category in categories" :key="category.id" :value="category.name">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.category" class="mt-2" />
                                </div>

                                <!-- Expense Date -->
                                <div>
                                    <InputLabel for="expense_date" value="Sana *" />
                                    <TextInput
                                        id="expense_date"
                                        v-model="form.expense_date"
                                        type="date"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.expense_date" class="mt-2" />
                                </div>
                            </div>

                            <!-- Title -->
                            <div>
                                <InputLabel for="title" value="Xarajat nomi *" />
                                <TextInput
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.title" class="mt-2" />
                            </div>

                            <!-- Description -->
                            <div>
                                <InputLabel for="description" value="Tavsifi" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                                <InputError :message="form.errors.description" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Amount -->
                                <div>
                                    <InputLabel for="amount" value="Summa *" />
                                    <TextInput
                                        id="amount"
                                        v-model="form.amount"
                                        type="number"
                                        step="0.01"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.amount" class="mt-2" />
                                </div>

                                <!-- Payment Method -->
                                <div>
                                    <InputLabel for="payment_method" value="To'lov usuli" />
                                    <select
                                        id="payment_method"
                                        v-model="form.payment_method"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option :value="null">Tanlang</option>
                                        <option value="Naqd">Naqd</option>
                                        <option value="Bank">Bank</option>
                                        <option value="Click">Click</option>
                                        <option value="Payme">Payme</option>
                                    </select>
                                    <InputError :message="form.errors.payment_method" class="mt-2" />
                                </div>
                            </div>

                            <!-- Receipt Number -->
                            <div>
                                <InputLabel for="receipt_number" value="Kvitansiya raqami" />
                                <TextInput
                                    id="receipt_number"
                                    v-model="form.receipt_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.receipt_number" class="mt-2" />
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">
                                    Saqlash
                                </PrimaryButton>
                                <Link
                                    :href="route('expenses.index')"
                                    class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                >
                                    Bekor qilish
                                </Link>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
