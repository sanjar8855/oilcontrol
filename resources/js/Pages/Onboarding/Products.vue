<script setup>
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    products: Array,
});

const DEFAULT_PRICES = {
    'Motor moyi (Cobalt)': { purchase_price: 50000, selling_price: 60000 },
    'Havo filtri (Cobalt)': { purchase_price: 20000, selling_price: 25000 },
    'Moy filtri (Cobalt)': { purchase_price: 15000, selling_price: 20000 },
};

const defaultValuesFor = (product) => ({
    stock_quantity: 10,
    purchase_price: DEFAULT_PRICES[product.name]?.purchase_price ?? 0,
    selling_price: DEFAULT_PRICES[product.name]?.selling_price ?? 0,
});

const selectedItems = reactive(new Map());

// Ro'yxatdagi barcha mahsulotlar boshidanoq tanlangan holatda ko'rinadi.
const selectAll = (products) => {
    products.forEach((product) => {
        if (!selectedItems.has(product.id)) {
            selectedItems.set(product.id, defaultValuesFor(product));
        }
    });
};
selectAll(props.products);
watch(() => props.products, selectAll);

const isSelected = (id) => selectedItems.has(id);

const toggle = (product) => {
    if (selectedItems.has(product.id)) {
        selectedItems.delete(product.id);
    } else {
        selectedItems.set(product.id, defaultValuesFor(product));
    }
};

const form = useForm({ items: [] });

const submit = () => {
    form.transform(() => ({
        items: Array.from(selectedItems, ([global_product_id, values]) => ({
            global_product_id,
            stock_quantity: values.stock_quantity,
            purchase_price: values.purchase_price,
            selling_price: values.selling_price,
        })),
    })).post(route('onboarding.products.store'));
};
</script>

<template>
    <Head title="Mahsulot tanlash" />

    <OnboardingLayout :step="1">
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            O'quv jarayoni o'rganish boshlandi, ma'lumotlarni kiriting
        </h2>

        <div v-if="form.errors.error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
            {{ form.errors.error }}
        </div>

        <div v-if="products.length === 0" class="mb-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
            Katalogda hozircha mahsulot yo'q.
        </div>

        <ul class="mb-4 max-h-[28rem] divide-y divide-gray-200 overflow-y-auto dark:divide-gray-700">
            <li v-for="product in products" :key="product.id" class="py-2">
                <div class="flex items-center gap-3">
                    <input
                        :id="`product-${product.id}`"
                        type="checkbox"
                        :checked="isSelected(product.id)"
                        @change="toggle(product)"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label :for="`product-${product.id}`" class="text-sm text-gray-700 dark:text-gray-300">
                        {{ product.name }} <span class="text-gray-400">({{ product.unit }})</span>
                    </label>
                </div>

                <div v-if="isSelected(product.id)" class="mt-2 grid grid-cols-3 gap-2 pl-7">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400">Miqdor ({{ product.unit }})</label>
                        <input
                            v-model.number="selectedItems.get(product.id).stock_quantity"
                            type="number"
                            min="0"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400">Kirim narxi (so'm)</label>
                        <input
                            v-model.number="selectedItems.get(product.id).purchase_price"
                            type="number"
                            min="0"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400">Chiqim narxi (so'm)</label>
                        <input
                            v-model.number="selectedItems.get(product.id).selling_price"
                            type="number"
                            min="0"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>
            </li>
        </ul>

        <button
            type="button"
            :disabled="selectedCount === 0 || form.processing"
            @click="submit"
            class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
        >
            Davom etish
        </button>
    </OnboardingLayout>
</template>
