<!-- resources/js/Pages/Onboarding/Products.vue -->
<script setup>
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    products: Array,
});

const selected = reactive(new Set());

const toggle = (id) => {
    if (selected.has(id)) {
        selected.delete(id);
    } else {
        selected.add(id);
    }
};

const selectedCount = computed(() => selected.size);

const form = useForm({ global_product_ids: [] });

const submit = () => {
    form.transform(() => ({ global_product_ids: Array.from(selected) }))
        .post(route('onboarding.products.store'));
};
</script>

<template>
    <Head title="Mahsulot tanlash" />

    <OnboardingLayout :step="1">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
            Kerakli mahsulotlarni tanlang
        </h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Umumiy katalogdan kamida 1 ta mahsulot tanlang — narx va qoldiqni keyinroq to'ldirasiz.
        </p>

        <div v-if="form.errors.error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
            {{ form.errors.error }}
        </div>

        <div v-if="products.length === 0" class="mb-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
            Katalogda hozircha mahsulot yo'q.
        </div>

        <ul class="mb-4 max-h-96 divide-y divide-gray-200 overflow-y-auto dark:divide-gray-700">
            <li v-for="product in products" :key="product.id" class="flex items-center gap-3 py-2">
                <input
                    :id="`product-${product.id}`"
                    type="checkbox"
                    :checked="selected.has(product.id)"
                    @change="toggle(product.id)"
                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label :for="`product-${product.id}`" class="text-sm text-gray-700 dark:text-gray-300">
                    {{ product.name }} <span class="text-gray-400">({{ product.unit }})</span>
                </label>
            </li>
        </ul>

        <button
            type="button"
            :disabled="selectedCount === 0 || form.processing"
            @click="submit"
            class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
        >
            Davom etish ({{ selectedCount }} ta tanlandi)
        </button>
    </OnboardingLayout>
</template>
