<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';

const props = defineProps({
    vehicle: Object,
    products: Array,
});

const showSaleForm = ref(false);
const cart = ref([]);
const selectedProductId = ref(null);
const selectedQuantity = ref(1);
const isManualMode = ref(false);
const manualProductName = ref('');
const manualProductPrice = ref('');

const serviceForm = useForm({
    vehicle_id: props.vehicle.id,
    service_date: new Date().toISOString().split('T')[0],
    odometer_reading: props.vehicle.service_logs?.[0]
        ? props.vehicle.service_logs[0].odometer_reading + props.vehicle.service_logs[0].next_service_km
        : '',
    next_service_km: 5000,
    avg_monthly_km: props.vehicle.avg_monthly_km || 1000,
    service_type: 'Servis',
    cost: 0,
    labor_cost: 0,
    notes: '',
    products: [],
    manual_items: [],
    payment_type: 'cash',
    payment_status: 'paid',
    paid_amount: 0,
});

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

const submitService = () => {
    if (!serviceForm.odometer_reading) {
        alert('Probegni kiriting');
        return;
    }

    if (cart.value.length === 0) {
        alert('Kamida bitta mahsulot qo\'shing');
        return;
    }

    const catalogItems = cart.value.filter(item => !item.is_manual);
    const manualItems = cart.value.filter(item => item.is_manual);

    serviceForm.products = catalogItems.map(item => ({
        id: item.id,
        quantity: item.quantity,
        unit_price: item.unit_price,
    }));

    serviceForm.labor_cost = manualItems.reduce((sum, item) => sum + item.total_price, 0);
    serviceForm.manual_items = manualItems.map(item => ({
        name: item.name,
        quantity: item.quantity,
        unit_price: item.unit_price,
        total_price: item.total_price,
    }));
    serviceForm.cost = cartTotal.value;
    serviceForm.paid_amount = cartTotal.value;

    serviceForm.post(route('service-logs.store'), {
        onSuccess: () => {
            cart.value = [];
            showSaleForm.value = false;
            isManualMode.value = false;
            serviceForm.reset();
        },
        onError: (errors) => {
            console.error('Xatolik:', errors);
        },
    });
};

const toggleSaleForm = () => {
    showSaleForm.value = !showSaleForm.value;
};
</script>

<template>
    <Head :title="`${vehicle.make} ${vehicle.model}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ vehicle.make }} {{ vehicle.model }}
                </h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('vehicles.edit', vehicle.id)"
                        class="rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500"
                    >
                        Tahrirlash
                    </Link>
                    <Link
                        :href="route('clients.show', vehicle.client.id)"
                        class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        ← Mijozga qaytish
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">

                <!-- Savdo tugmasi -->
                <div class="mb-6">
                    <button
                        @click="toggleSaleForm"
                        class="w-full rounded-md bg-green-600 px-6 py-3 text-lg font-semibold text-white shadow-sm hover:bg-green-500"
                    >
                        {{ showSaleForm ? '✕ Yopish' : '🛒 Savdo qilish' }}
                    </button>
                </div>

                <!-- Servis formasi -->
                <div v-if="showSaleForm" class="mb-6 overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="border-b border-gray-200 bg-white px-4 py-4 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                            Yangi servis va mahsulot sotish
                        </h3>
                    </div>
                    <div class="p-4 sm:p-6">
                        <form @submit.prevent="submitService">

                            <!-- Servis ma'lumotlari: 3 ustun -->
                            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Servis sanasi <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="serviceForm.service_date"
                                        type="date"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Probeg (km) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="serviceForm.odometer_reading"
                                        type="number"
                                        required
                                        min="0"
                                        placeholder="125000"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Nechi km keyin servis <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="serviceForm.next_service_km"
                                        type="number"
                                        required
                                        min="1000"
                                        max="50000"
                                        placeholder="5000"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- Mahsulotlar qo'shish -->
                            <div class="mb-4">
                                <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    Mahsulotlar qo'shish
                                </h4>

                                <div class="flex items-end gap-2">

                                    <!-- Catalog yoki Manual input (flex-grow) -->
                                    <div class="min-w-0 flex-1">
                                        <!-- Catalog rejim -->
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
                                                <!-- Qo'lda kiritishga o'tish -->
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

                                        <!-- Manual rejim -->
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
                                                <!-- Katalogga qaytish -->
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

                                    <!-- Miqdor (kichik) -->
                                    <div class="w-16 shrink-0">
                                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Miqdor
                                        </label>
                                        <input
                                            v-model="selectedQuantity"
                                            type="number"
                                            step="0.1"
                                            placeholder="1"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                    </div>

                                    <!-- Qo'shish tugmasi -->
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
                                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 text-right">Jami:</td>
                                                <td class="px-4 py-2 text-sm font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                                    {{ cartTotal.toLocaleString() }} so'm
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Izoh -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Izoh
                                </label>
                                <textarea
                                    v-model="serviceForm.notes"
                                    rows="3"
                                    placeholder="Qo'shimcha ma'lumot..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                ></textarea>
                            </div>

                            <!-- Tugmalar -->
                            <div class="flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="toggleSaleForm"
                                    class="rounded-md bg-gray-200 px-6 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    Bekor qilish
                                </button>
                                <button
                                    type="submit"
                                    :disabled="serviceForm.processing || cart.length === 0"
                                    class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    {{ serviceForm.processing ? 'Saqlanmoqda...' : 'Savdoni saqlash' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Avtomobil ma'lumotlari -->
                    <div class="lg:col-span-1">
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                            <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                    Avtomobil Ma'lumotlari
                                </h3>
                            </div>
                            <div class="p-6">
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Egasi</dt>
                                        <dd class="mt-1">
                                            <Link
                                                :href="route('clients.show', vehicle.client.id)"
                                                class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                            >
                                                {{ vehicle.client.name }}
                                            </Link>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Marka</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ vehicle.make }}</dd>
                                    </div>
                                    <div v-if="vehicle.model">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Model</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ vehicle.model }}</dd>
                                    </div>
                                    <div v-if="vehicle.year">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Yili</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ vehicle.year }}</dd>
                                    </div>
                                    <div v-if="vehicle.plate_number">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Davlat raqami</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ vehicle.plate_number }}</dd>
                                    </div>
                                    <div v-if="vehicle.vin">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">VIN</dt>
                                        <dd class="mt-1 text-xs text-gray-900 dark:text-white">{{ vehicle.vin }}</dd>
                                    </div>
                                    <div v-if="vehicle.avg_monthly_km">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">O'rtacha (kuniga)</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                            ~{{ Math.round(vehicle.avg_monthly_km / 30).toLocaleString() }} km
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Servis tarixi -->
                    <div class="lg:col-span-2">
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                            <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                        Servis Tarixi
                                    </h3>
                                </div>
                            </div>
                            <div class="p-6">
                                <div v-if="vehicle.service_logs && vehicle.service_logs.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <div
                                        v-for="log in vehicle.service_logs"
                                        :key="log.id"
                                        class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                    >
                                        <!-- Chap: asosiy ma'lumot -->
                                        <div class="min-w-0 flex-1">
                                            <!-- Sana + probeg + keyingi servis bir qatorda -->
                                            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm">
                                                <span class="font-medium text-gray-900 dark:text-white">
                                                    {{ new Date(log.service_date).toLocaleDateString('uz-UZ') }}
                                                </span>
                                                <span class="text-gray-400">·</span>
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    {{ log.odometer_reading.toLocaleString() }} km
                                                </span>
                                                <span class="text-gray-400">→</span>
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    {{ (log.odometer_reading + log.next_service_km).toLocaleString() }} km
                                                </span>
                                                <span v-if="log.total_amount" class="font-semibold text-indigo-600 dark:text-indigo-400">
                                                    {{ Number(log.total_amount).toLocaleString() }} so'm
                                                </span>
                                            </div>

                                            <!-- Mahsulotlar kichik chip sifatida -->
                                            <div v-if="log.products && log.products.length > 0" class="mt-1.5 flex flex-wrap gap-1">
                                                <span
                                                    v-for="product in log.products"
                                                    :key="product.id"
                                                    class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                                >
                                                    {{ product.name }}
                                                    <span class="ml-1 text-gray-400">×{{ product.pivot.quantity }}</span>
                                                </span>
                                            </div>

                                            <!-- Izoh -->
                                            <p v-if="log.notes" class="mt-1 truncate text-xs text-gray-400 dark:text-gray-500">
                                                {{ log.notes }}
                                            </p>
                                        </div>

                                        <!-- O'ng: Batafsil tugma -->
                                        <Link
                                            :href="route('service-logs.show', log.id)"
                                            class="shrink-0 rounded bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                        >
                                            Batafsil
                                        </Link>
                                    </div>
                                </div>
                                <div v-else class="py-8 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Servis yozuvlari yo'q
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Birinchi servis yozuvini qo'shing
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
