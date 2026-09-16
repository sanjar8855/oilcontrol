<script setup>
defineProps({
    brands: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['select-brand', 'select-category']);
</script>

<template>
    <div>
        <div v-if="brands.length > 0" class="mb-6">
            <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Brend bo'yicha</h3>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                <button
                    v-for="brand in brands"
                    :key="brand.id"
                    type="button"
                    @click="emit('select-brand', brand.id)"
                    class="flex flex-col items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-4 text-center shadow-sm transition hover:border-indigo-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                >
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ brand.name }}</span>
                    <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ brand.products_count }} ta mahsulot</span>
                </button>
            </div>
        </div>

        <div v-if="categories.length > 0">
            <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Kategoriya bo'yicha</h3>
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    @click="emit('select-category', category.id)"
                    class="flex w-full items-center justify-between border-b border-gray-100 px-4 py-3 text-left last:border-b-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                >
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ category.name }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ category.products_count }} ta mahsulot</span>
                </button>
            </div>
        </div>

        <p v-if="brands.length === 0 && categories.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
            Hali brend yoki kategoriya qo'shilmagan
        </p>
    </div>
</template>
