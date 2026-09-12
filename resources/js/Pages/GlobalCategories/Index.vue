<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    categories: Array,
});

const showModal = ref(false);
const modalMode = ref('create'); // 'create' | 'edit'
const editingCategory = ref(null);
const form = useForm({ name: '', is_active: true });

const openCreateModal = () => {
    modalMode.value = 'create';
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showModal.value = true;
};
const openEditModal = (category) => {
    modalMode.value = 'edit';
    editingCategory.value = category;
    form.clearErrors();
    form.name = category.name;
    form.is_active = category.is_active;
    showModal.value = true;
};
const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};
const submitModal = () => {
    if (modalMode.value === 'create') {
        form.post(route('global-categories.store'), {
            preserveScroll: true,
            onSuccess: closeModal,
        });
    } else {
        form.put(route('global-categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: closeModal,
        });
    }
};

const deleteCategory = (category) => {
    if (confirm(`Haqiqatan ham "${category.name}" kategoriyasini o'chirmoqchimisiz?`)) {
        router.delete(route('global-categories.destroy', category.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Global kategoriyalar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Global kategoriyalar
                </h2>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi kategoriya qo'shish
                </button>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-3xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div v-if="categories.length === 0" class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        Hali kategoriyalar yo'q. Yuqoridagi tugma orqali birinchi kategoriyani qo'shing.
                    </div>
                    <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="flex items-center justify-between gap-3 p-4"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="truncate font-medium text-gray-900 dark:text-white">{{ category.name }}</span>
                                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ category.global_products_count }} ta mahsulot
                                </span>
                                <span
                                    v-if="!category.is_active"
                                    class="shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900 dark:text-red-200"
                                >
                                    Nofaol
                                </span>
                            </div>
                            <div class="flex shrink-0 gap-3 text-sm font-medium">
                                <button
                                    @click="openEditModal(category)"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                >
                                    O'zgartirish
                                </button>
                                <button
                                    @click="deleteCategory(category)"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    O'chirish
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal" max-width="sm">
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    {{ modalMode === 'create' ? 'Yangi kategoriya qo\'shish' : 'Kategoriyani o\'zgartirish' }}
                </h2>
                <form @submit.prevent="submitModal" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Kategoriya nomi
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            autofocus
                            placeholder="Masalan: Filtrlar"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <div v-if="modalMode === 'edit'" class="flex items-center gap-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800"
                        />
                        <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Faol</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                        >
                            Saqlash
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
