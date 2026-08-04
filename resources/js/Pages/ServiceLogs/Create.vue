<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';

const props = defineProps({
    vehicles: Array,
    products: Array,
    selectedVehicleId: [String, Number],
});

const form = useForm({
    vehicle_id: props.selectedVehicleId || null,
    service_date: new Date().toLocaleDateString('sv-SE'),
    odometer_reading: '',
    next_service_km: 5000,
    avg_monthly_km: '',
    service_type: 'oil_change',
    cost: 0,
    labor_cost: 0,
    notes: '',
    products: [],
    manual_items: [],
    cash_amount: 0,
    click_amount: 0,
    is_credit: false,
    due_date: '',
});

const vehicleOptions = computed(() => {
    return props.vehicles.map((v) => ({ value: v.id, label: v.label }));
});

const carModelInfo = ref(null);
const loadingCarModelInfo = ref(false);

watch(() => form.vehicle_id, async (vehicleId) => {
    carModelInfo.value = null;
    if (!vehicleId) {
        return;
    }
    loadingCarModelInfo.value = true;
    try {
        const response = await axios.get(route('vehicles.car-model-info', vehicleId));
        carModelInfo.value = response.data.carModelInfo;
    } catch (error) {
        console.error('Avtomobil ma\'lumotini olishda xatolik:', error);
    } finally {
        loadingCarModelInfo.value = false;
    }
}, { immediate: true });

const cart = ref([]);
const selectedProductId = ref(null);
const selectedQuantity = ref(1);
const isManualMode = ref(false);
const manualProductName = ref('');
const manualProductPrice = ref('');

const productOptions = computed(() => {
    return props.products.map(p => ({
        value: p.id,
        label: `${p.name} - ${p.selling_price.toLocaleString()} so'm (${p.stock_quantity} ${p.unit})`,
        product: p,
    }));
});

const cartTotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.total_price, 0);
});

const laborCostValue = computed(() => parseFloat(form.labor_cost) || 0);

const grandTotal = computed(() => cartTotal.value + laborCostValue.value);

const cashAmountValue = computed(() => parseFloat(form.cash_amount) || 0);
const clickAmountValue = computed(() => parseFloat(form.click_amount) || 0);
const paidTotal = computed(() => cashAmountValue.value + clickAmountValue.value);
const remainingAmount = computed(() => Math.max(grandTotal.value - paidTotal.value, 0));

const fillFullCash = () => {
    form.cash_amount = grandTotal.value;
    form.click_amount = 0;
};

const toggleManualMode = () => {
    isManualMode.value = !isManualMode.value;
    selectedProductId.value = null;
    manualProductName.value = '';
    manualProductPrice.value = '';
    selectedQuantity.value = 1;
};

const addToCart = () => {
    const qty = parseFloat(selectedQuantity.value) || 0;
    if (qty <= 0) {
        alert('Miqdorni kiriting');
        return;
    }

    if (isManualMode.value) {
        if (!manualProductName.value.trim()) {
            alert('Mahsulot nomini kiriting');
            return;
        }
        const price = parseFloat(manualProductPrice.value) || 0;
        cart.value.push({
            id: null,
            name: manualProductName.value.trim(),
            unit: '',
            quantity: qty,
            unit_price: price,
            total_price: qty * price,
            is_manual: true,
        });
        manualProductName.value = '';
        manualProductPrice.value = '';
        selectedQuantity.value = 1;
        return;
    }

    if (!selectedProductId.value) {
        alert('Mahsulotni tanlang');
        return;
    }

    const product = props.products.find(p => p.id === selectedProductId.value);
    if (!product) return;

    if (qty > product.stock_quantity) {
        alert(`Omborda faqat ${product.stock_quantity} ${product.unit} mavjud`);
        return;
    }

    const existingIndex = cart.value.findIndex(item => !item.is_manual && item.id === product.id);
    if (existingIndex !== -1) {
        cart.value[existingIndex].quantity += qty;
        cart.value[existingIndex].total_price = cart.value[existingIndex].quantity * product.selling_price;
    } else {
        cart.value.push({
            id: product.id,
            name: product.name,
            unit: product.unit,
            quantity: qty,
            unit_price: product.selling_price,
            total_price: qty * product.selling_price,
            is_manual: false,
        });
    }

    selectedProductId.value = null;
    selectedQuantity.value = 1;
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const quickAddRecommended = (recommended) => {
    if (recommended.quantity > recommended.stock_quantity) {
        alert(`Omborda faqat ${recommended.stock_quantity} ${recommended.unit} mavjud`);
        return;
    }

    const price = Number(recommended.selling_price);
    const existingIndex = cart.value.findIndex(item => !item.is_manual && item.id === recommended.id);
    if (existingIndex !== -1) {
        cart.value[existingIndex].quantity += recommended.quantity;
        cart.value[existingIndex].total_price = cart.value[existingIndex].quantity * price;
    } else {
        cart.value.push({
            id: recommended.id,
            name: recommended.name,
            unit: recommended.unit,
            quantity: recommended.quantity,
            unit_price: price,
            total_price: recommended.quantity * price,
            is_manual: false,
        });
    }
};

const submit = () => {
    if (!form.vehicle_id) {
        alert('Avtomobilni tanlang');
        return;
    }
    if (cart.value.length === 0) {
        alert('Kamida bitta mahsulot qo\'shing');
        return;
    }

    const catalogItems = cart.value.filter(item => !item.is_manual);
    const manualItems = cart.value.filter(item => item.is_manual);

    form.products = catalogItems.map(item => ({
        id: item.id,
        quantity: item.quantity,
        unit_price: item.unit_price,
    }));

    form.manual_items = manualItems.map(item => ({
        name: item.name,
        quantity: item.quantity,
        unit_price: item.unit_price,
        total_price: item.total_price,
    }));

    form.cost = grandTotal.value;

    form.post(route('service-logs.store'));
};
</script>

<template>
    <Head title="Yangi Servis Yozuvi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Yangi Servis Yozuvi
                </h2>
                <Link
                    :href="route('vehicles.index')"
                    class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                >
                    ← Orqaga
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-4xl px-3 sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Avtomobil tanlash -->
                            <div>
                                <InputLabel for="vehicle_id" value="Avtomobil *" />
                                <Multiselect
                                    id="vehicle_id"
                                    v-model="form.vehicle_id"
                                    :options="vehicleOptions"
                                    :searchable="true"
                                    placeholder="Avtomobilni tanlang"
                                    noOptionsText="Avtomobil topilmadi"
                                    noResultsText="Natija topilmadi"
                                    class="mt-1"
                                />
                                <InputError class="mt-2" :message="form.errors.vehicle_id" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="service_date" value="Servis sanasi *" />
                                    <TextInput
                                        id="service_date"
                                        v-model="form.service_date"
                                        type="date"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.service_date" />
                                </div>

                                <div>
                                    <InputLabel for="service_type" value="Servis turi *" />
                                    <select
                                        id="service_type"
                                        v-model="form.service_type"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="oil_change">Moy almashtirish</option>
                                        <option value="filter_change">Filtr almashtirish</option>
                                        <option value="full_service">To'liq servis</option>
                                        <option value="inspection">Ko'rik</option>
                                        <option value="repair">Ta'mirlash</option>
                                        <option value="other">Boshqa</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.service_type" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <div>
                                    <InputLabel for="odometer_reading" value="Hozirgi probeg (km) *" />
                                    <TextInput
                                        id="odometer_reading"
                                        v-model="form.odometer_reading"
                                        type="number"
                                        class="mt-1 block w-full"
                                        required
                                        min="0"
                                        placeholder="85000"
                                    />
                                    <InputError class="mt-2" :message="form.errors.odometer_reading" />
                                </div>

                                <div>
                                    <InputLabel for="next_service_km" value="Keyingi servis (km) *" />
                                    <TextInput
                                        id="next_service_km"
                                        v-model="form.next_service_km"
                                        type="number"
                                        class="mt-1 block w-full"
                                        required
                                        min="1000"
                                        max="50000"
                                        placeholder="5000"
                                    />
                                    <InputError class="mt-2" :message="form.errors.next_service_km" />
                                </div>

                                <div>
                                    <InputLabel for="avg_monthly_km" value="Oylik km" />
                                    <TextInput
                                        id="avg_monthly_km"
                                        v-model="form.avg_monthly_km"
                                        type="number"
                                        class="mt-1 block w-full"
                                        min="0"
                                        placeholder="1000"
                                    />
                                    <InputError class="mt-2" :message="form.errors.avg_monthly_km" />
                                </div>
                            </div>

                            <!-- Avtomobil turiga tavsiyalar -->
                            <div
                                v-if="carModelInfo && (carModelInfo.oil_capacity_liters || carModelInfo.antifreeze_capacity_min_liters || carModelInfo.recommended_products.length > 0)"
                                class="rounded-md bg-indigo-50 p-4 dark:bg-indigo-900/20"
                            >
                                <h4 class="mb-2 text-sm font-semibold text-indigo-900 dark:text-indigo-200">
                                    Tanlangan avtomobil uchun tavsiyalar
                                </h4>
                                <div v-if="carModelInfo.oil_capacity_liters || carModelInfo.antifreeze_capacity_min_liters" class="mb-3 flex flex-wrap gap-2">
                                    <span
                                        v-if="carModelInfo.oil_capacity_liters"
                                        class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200"
                                    >
                                        🛢 Motor moyi: {{ carModelInfo.oil_capacity_liters }} L
                                    </span>
                                    <span
                                        v-if="carModelInfo.antifreeze_capacity_min_liters || carModelInfo.antifreeze_capacity_max_liters"
                                        class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-800 dark:bg-sky-900 dark:text-sky-200"
                                    >
                                        ❄️ Antifriz: {{ carModelInfo.antifreeze_capacity_min_liters }}-{{ carModelInfo.antifreeze_capacity_max_liters }} L
                                    </span>
                                </div>
                                <div v-if="carModelInfo.recommended_products.length > 0" class="flex flex-wrap gap-2">
                                    <button
                                        v-for="product in carModelInfo.recommended_products"
                                        :key="product.id"
                                        type="button"
                                        @click="quickAddRecommended(product)"
                                        class="inline-flex items-center gap-1 rounded-md border border-indigo-300 bg-white px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:bg-gray-800 dark:text-indigo-300 dark:hover:bg-gray-700"
                                    >
                                        + {{ product.name }} ({{ product.quantity }} {{ product.unit }})
                                    </button>
                                </div>
                            </div>

                            <!-- Mahsulotlar qo'shish -->
                            <div class="mb-4">
                                <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    Mahsulotlar qo'shish
                                </h4>

                                <div class="flex items-end gap-2">
                                    <div class="min-w-0 flex-1">
                                        <template v-if="!isManualMode">
                                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Mahsulot tanlang
                                            </label>
                                            <div class="flex items-center gap-1">
                                                <div class="min-w-0 flex-1">
                                                    <Multiselect
                                                        v-model="selectedProductId"
                                                        :options="productOptions"
                                                        :searchable="true"
                                                        placeholder="Mahsulot qidirish..."
                                                        noOptionsText="Mahsulot topilmadi"
                                                        noResultsText="Natija topilmadi"
                                                    />
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="toggleManualMode"
                                                    title="Qo'lda kiritish"
                                                    class="shrink-0 rounded-md border border-gray-300 bg-white p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>

                                        <template v-else>
                                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Qo'lda kiritish
                                            </label>
                                            <div class="flex items-center gap-1">
                                                <input
                                                    v-model="manualProductName"
                                                    type="text"
                                                    placeholder="Mahsulot nomi"
                                                    class="min-w-0 flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                />
                                                <input
                                                    v-model="manualProductPrice"
                                                    type="number"
                                                    min="0"
                                                    placeholder="Narxi"
                                                    class="w-28 shrink-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                />
                                                <button
                                                    type="button"
                                                    @click="toggleManualMode"
                                                    title="Katalogdan tanlash"
                                                    class="shrink-0 rounded-md border border-gray-300 bg-white p-2 text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="w-16 shrink-0">
                                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Miqdor
                                        </label>
                                        <input
                                            v-model="selectedQuantity"
                                            type="number"
                                            step="any"
                                            placeholder="1"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                    </div>

                                    <div class="shrink-0">
                                        <label class="mb-1 block text-sm font-medium text-transparent">_</label>
                                        <button
                                            type="button"
                                            @click="addToCart"
                                            class="whitespace-nowrap rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                        >
                                            + Qo'shish
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Savatcha -->
                            <div v-if="cart.length > 0" class="mb-6">
                                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Mahsulot</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Miqdor</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Narx</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Jami</th>
                                                <th class="px-4 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                            <tr v-for="(item, index) in cart" :key="index">
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                    {{ item.name }}
                                                    <span v-if="item.is_manual" class="ml-1 rounded bg-yellow-100 px-1 py-0.5 text-xs text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                                                        qo'lda
                                                    </span>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                    {{ item.quantity }} {{ item.unit }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                    {{ item.unit_price.toLocaleString() }} so'm
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ item.total_price.toLocaleString() }} so'm
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                                    <button
                                                        type="button"
                                                        @click="removeFromCart(index)"
                                                        class="text-red-600 hover:text-red-800 dark:text-red-400"
                                                    >
                                                        O'chirish
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Xizmat haqqi va jami -->
                            <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="labor_cost" value="Ish haqi" />
                                        <TextInput
                                            id="labor_cost"
                                            v-model="form.labor_cost"
                                            type="number"
                                            class="mt-1 block w-full"
                                            min="0"
                                            step="any"
                                            placeholder="50000"
                                        />
                                        <InputError class="mt-2" :message="form.errors.labor_cost" />
                                    </div>

                                    <div class="flex flex-col justify-end">
                                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">Mahsulotlar:</span>
                                                <span class="font-medium text-gray-900 dark:text-white">
                                                    {{ cartTotal.toLocaleString() }} so'm
                                                </span>
                                            </div>
                                            <div class="mt-2 flex justify-between text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">Ish haqi:</span>
                                                <span class="font-medium text-gray-900 dark:text-white">
                                                    {{ laborCostValue.toLocaleString() }} so'm
                                                </span>
                                            </div>
                                            <div class="mt-3 flex justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                                                <span class="font-semibold text-gray-900 dark:text-white">Jami:</span>
                                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                                    {{ grandTotal.toLocaleString() }} so'm
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- To'lov -->
                            <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
                                <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    To'lov
                                </h4>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="cash_amount" value="Naqd" />
                                        <TextInput
                                            id="cash_amount"
                                            v-model="form.cash_amount"
                                            type="number"
                                            class="mt-1 block w-full"
                                            min="0"
                                            step="any"
                                            placeholder="0"
                                        />
                                        <InputError class="mt-2" :message="form.errors.cash_amount" />
                                    </div>

                                    <div>
                                        <InputLabel for="click_amount" value="Click" />
                                        <TextInput
                                            id="click_amount"
                                            v-model="form.click_amount"
                                            type="number"
                                            class="mt-1 block w-full"
                                            min="0"
                                            step="any"
                                            placeholder="0"
                                        />
                                        <InputError class="mt-2" :message="form.errors.click_amount" />
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    @click="fillFullCash"
                                    class="mt-2 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                >
                                    To'liq summani naqdga qo'yish
                                </button>

                                <div class="mt-4 flex items-center gap-2">
                                    <input
                                        id="is_credit"
                                        v-model="form.is_credit"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                    />
                                    <label for="is_credit" class="text-sm text-gray-900 dark:text-gray-300">
                                        Qolgan summa nasiyaga qoldirilsin
                                    </label>
                                </div>

                                <div v-if="form.is_credit" class="mt-2 max-w-xs">
                                    <InputLabel for="due_date" value="Nasiya muddati" />
                                    <TextInput
                                        id="due_date"
                                        v-model="form.due_date"
                                        type="date"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.due_date" />
                                </div>

                                <div class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">To'langan (naqd + Click):</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ paidTotal.toLocaleString() }} so'm
                                        </span>
                                    </div>
                                    <div class="mt-2 flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">
                                            {{ form.is_credit ? 'Nasiyadagi summa:' : 'Qolgan (to\'lanmagan):' }}
                                        </span>
                                        <span
                                            class="font-semibold"
                                            :class="remainingAmount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'"
                                        >
                                            {{ remainingAmount.toLocaleString() }} so'm
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Eslatmalar -->
                            <div>
                                <InputLabel for="notes" value="Eslatmalar (ixtiyoriy)" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                                    placeholder="Qanday ishlar bajarildi..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.notes" />
                            </div>

                            <!-- Tugmalar -->
                            <div class="flex items-center justify-end gap-4">
                                <Link
                                    :href="route('vehicles.index')"
                                    class="rounded-md px-4 py-2 text-sm font-semibold text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                >
                                    Bekor qilish
                                </Link>
                                <PrimaryButton :disabled="form.processing || cart.length === 0">
                                    {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
