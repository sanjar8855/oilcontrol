<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    branches: Array,
});

const form = useForm({
    branch_id: '',
    notes: '',
});

const submit = () => {
    form.post(route('inventories.store'));
};
</script>

<template>
    <Head title="Yangi inventarizatsiya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Yangi inventarizatsiya
                </h2>
                <Link
                    :href="route('inventories.index')"
                    class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                >
                    ← Orqaga
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-2xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                            Boshlashda tizim tanlangan filial (yoki barcha filiallar) bo'yicha barcha kuzatiladigan
                            mahsulotlarning joriy qoldig'ini suratga oladi. Keyin har bir mahsulotni sanab, haqiqiy
                            miqdorini kiritasiz.
                        </p>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div v-if="branches.length > 0">
                                <label for="branch_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Filial
                                </label>
                                <select
                                    id="branch_id"
                                    v-model="form.branch_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">Barcha filiallar</option>
                                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                                        {{ branch.name }} ({{ branch.code }})
                                    </option>
                                </select>
                                <div v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.branch_id }}
                                </div>
                            </div>

                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Izoh
                                </label>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    placeholder="Masalan: chorak yakuni inventarizatsiyasi"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                ></textarea>
                                <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.notes }}
                                </div>
                            </div>

                            <div class="flex justify-end gap-3">
                                <Link
                                    :href="route('inventories.index')"
                                    class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    Bekor qilish
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Boshlanmoqda...' : 'Boshlash' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
