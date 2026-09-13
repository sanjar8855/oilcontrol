<script setup>
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    vehicle: Object,
    products: Array,
});

const selectedItems = reactive(new Map());

props.products.forEach((product) => {
    selectedItems.set(product.id, { quantity: 1, unit_price: product.selling_price });
});

const isSelected = (id) => selectedItems.has(id);

const toggle = (product) => {
    if (selectedItems.has(product.id)) {
        selectedItems.delete(product.id);
    } else {
        selectedItems.set(product.id, { quantity: 1, unit_price: product.selling_price });
    }
};

const selectedCount = computed(() => selectedItems.size);

const itemTotal = (id) => {
    const item = selectedItems.get(id);
    return item ? item.quantity * item.unit_price : 0;
};

const grandTotal = computed(() => {
    let total = 0;
    selectedItems.forEach((item) => {
        total += item.quantity * item.unit_price;
    });
    return total;
});

const form = useForm({
    odometer_reading: 0,
});

const submit = () => {
    form.transform((data) => ({
        vehicle_id: props.vehicle.id,
        service_date: new Date().toLocaleDateString('sv-SE'),
        odometer_reading: data.odometer_reading,
        next_service_km: 5000,
        service_type: 'Birinchi servis',
        products: Array.from(selectedItems, ([id, values]) => ({
            id,
            quantity: values.quantity,
            unit_price: values.unit_price,
        })),
    })).post(route('service-logs.store'));
};
</script>

<template>
    <Head title="Birinchi savdo" />

    <OnboardingLayout :step="3">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
            Birinchi savdoni amalga oshiring
        </h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            {{ vehicle.client.name }} — {{ vehicle.plate_number }} ({{ vehicle.make }})
        </p>

        <div v-if="form.errors.error" class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
            {{ form.errors.error }}
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sanoq (km)</label>
            <input
                v-model.number="form.odometer_reading"
                type="number"
                min="0"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
            />
            <p v-if="form.errors.odometer_reading" class="mt-1 text-sm text-red-600">{{ form.errors.odometer_reading }}</p>
        </div>

        <ul class="mb-4 divide-y divide-gray-200 dark:divide-gray-700">
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

                <div v-if="isSelected(product.id)" class="mt-2 grid grid-cols-2 gap-2 pl-7">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400">Miqdor</label>
                        <input
                            v-model.number="selectedItems.get(product.id).quantity"
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400">Narxi (so'm)</label>
                        <input
                            v-model.number="selectedItems.get(product.id).unit_price"
                            type="number"
                            min="0"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>

                <p v-if="isSelected(product.id)" class="mt-1 pl-7 text-right text-xs text-gray-500 dark:text-gray-400">
                    Jami: {{ itemTotal(product.id).toLocaleString() }} so'm
                </p>
            </li>
        </ul>

        <div class="mb-4 flex items-center justify-between rounded-md bg-gray-50 p-3 text-sm font-semibold text-gray-900 dark:bg-gray-900 dark:text-white">
            <span>Savdoning jami summasi</span>
            <span>{{ grandTotal.toLocaleString() }} so'm</span>
        </div>

        <button
            type="button"
            :disabled="selectedCount === 0 || form.processing"
            @click="submit"
            class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:cursor-not-allowed disabled:opacity-50"
        >
            Savdoni yakunlash
        </button>
    </OnboardingLayout>
</template>
