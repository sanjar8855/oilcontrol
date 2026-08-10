<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
    suppliers: Array,
});

const emptyRow = () => ({
    name: '',
    category_id: null,
    supplier_id: null,
    unit: 'dona',
    purchase_price: 0,
    selling_price: 0,
    stock_quantity: 0,
    min_stock_level: 0,
});

const form = useForm({
    items: [emptyRow(), emptyRow(), emptyRow()],
});

const addRow = () => {
    form.items.push(emptyRow());
};

const removeRow = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('products.bulk-store'));
};
</script>

<template>
    <Head title="Ommaviy mahsulot kiritish" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Ommaviy mahsulot kiritish
            </h2>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <div class="mb-4 rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Yangi yuk kelganda bir nechta yangi mahsulotni birdaniga kiritish uchun ishlating. Har bir qatorga alohida mahsulot ma'lumotlarini kiriting.
                    </p>
                </div>

                <div v-if="form.errors.error" class="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                    <p class="text-sm text-red-800 dark:text-red-200">{{ form.errors.error }}</p>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="p-4 sm:p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nomi *</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kategoriya</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Ta'minotchi</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Birlik</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tan narxi *</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sotuv narxi *</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Miqdor</th>
                                        <th class="px-2 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="(item, index) in form.items" :key="index">
                                        <td class="px-2 py-2">
                                            <input
                                                v-model="item.name"
                                                type="text"
                                                required
                                                placeholder="Mahsulot nomi"
                                                class="block w-40 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            />
                                            <InputError :message="form.errors[`items.${index}.name`]" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <select
                                                v-model="item.category_id"
                                                class="block w-36 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            >
                                                <option :value="null">Tanlang</option>
                                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                                    {{ category.name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <select
                                                v-model="item.supplier_id"
                                                class="block w-36 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            >
                                                <option :value="null">Tanlanmagan</option>
                                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                                    {{ supplier.name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <select
                                                v-model="item.unit"
                                                class="block w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            >
                                                <option value="dona">Dona</option>
                                                <option value="litr">Litr</option>
                                                <option value="kg">Kg</option>
                                                <option value="metr">Metr</option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input
                                                v-model="item.purchase_price"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                required
                                                class="block w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            />
                                            <InputError :message="form.errors[`items.${index}.purchase_price`]" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <input
                                                v-model="item.selling_price"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                required
                                                class="block w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            />
                                            <InputError :message="form.errors[`items.${index}.selling_price`]" class="mt-1" />
                                        </td>
                                        <td class="px-2 py-2">
                                            <input
                                                v-model="item.stock_quantity"
                                                type="number"
                                                min="0"
                                                class="block w-20 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            />
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            <button
                                                type="button"
                                                @click="removeRow(index)"
                                                :disabled="form.items.length <= 1"
                                                class="text-red-600 hover:text-red-900 disabled:opacity-30 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                O'chirish
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <button
                            type="button"
                            @click="addRow"
                            class="mt-4 rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                        >
                            + Qator qo'shish
                        </button>

                        <div class="mt-6 flex items-center gap-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <PrimaryButton :disabled="form.processing">
                                Barchasini saqlash
                            </PrimaryButton>
                            <Link
                                :href="route('products.index')"
                                class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
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
