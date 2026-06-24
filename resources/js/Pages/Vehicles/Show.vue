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

// Savdo qilish formasi
const showSaleForm = ref(false);
const cart = ref([]);
const selectedProductId = ref(null);
const selectedQuantity = ref(1);

// Service log formasi
const serviceForm = useForm({
    vehicle_id: props.vehicle.id,
    service_date: new Date().toISOString().split('T')[0],
    odometer_reading: '',
    next_service_km: 10000,
    avg_monthly_km: props.vehicle.client.default_avg_monthly_km || 1000,
    service_type: 'Moy almashtirish',
    cost: 0,
    labor_cost: 0,
    notes: '',
    products: [],
});

// Mahsulotlar ro'yxati select uchun
const productOptions = computed(() => {
    return props.products.map(p => ({
        value: p.id,
        label: `${p.name} - ${p.selling_price.toLocaleString()} so'm (${p.stock_quantity} ${p.unit})`,
        product: p,
    }));
});

// Savatchaga qo'shish
const addToCart = () => {
    if (!selectedProductId.value) {
        alert('Mahsulotni tanlang');
        return;
    }

    const product = props.products.find(p => p.id === selectedProductId.value);
    if (!product) return;

    if (selectedQuantity.value <= 0) {
        alert('Miqdorni kiriting');
        return;
    }

    if (selectedQuantity.value > product.stock_quantity) {
        alert(`Omborda faqat ${product.stock_quantity} ${product.unit} mavjud`);
        return;
    }

    // Savatchada bor-yo'qligini tekshirish
    const existingIndex = cart.value.findIndex(item => item.id === product.id);

    if (existingIndex !== -1) {
        // Mavjud bo'lsa, miqdorni oshirish
        cart.value[existingIndex].quantity += selectedQuantity.value;
        cart.value[existingIndex].total_price = cart.value[existingIndex].quantity * product.selling_price;
    } else {
        // Yangi qo'shish
        cart.value.push({
            id: product.id,
            name: product.name,
            unit: product.unit,
            quantity: selectedQuantity.value,
            unit_price: product.selling_price,
            total_price: selectedQuantity.value * product.selling_price,
        });
    }

    // Reset
    selectedProductId.value = null;
    selectedQuantity.value = 1;

    // Jami narxni yangilash
    updateTotalCost();
};

// Savatchadan o'chirish
const removeFromCart = (index) => {
    cart.value.splice(index, 1);
    updateTotalCost();
};

// Jami narxni hisoblash
const updateTotalCost = () => {
    const productsTotal = cart.value.reduce((sum, item) => sum + item.total_price, 0);
    serviceForm.cost = productsTotal + (serviceForm.labor_cost || 0);
};

// Serverni saqlash
const submitService = () => {
    if (!serviceForm.odometer_reading) {
        alert('Probegni kiriting');
        return;
    }

    if (cart.value.length === 0) {
        alert('Kamida bitta mahsulot qo\'shing');
        return;
    }

    // Savatchadagi mahsulotlarni formaga ko'chirish
    serviceForm.products = cart.value.map(item => ({
        id: item.id,
        quantity: item.quantity,
        unit_price: item.unit_price,
    }));

    serviceForm.post(route('service-logs.store'), {
        onSuccess: () => {
            // Reset
            cart.value = [];
            showSaleForm.value = false;
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
                <!-- Savdo qilish tugmasi -->
                <div class="mb-6">
                    <button
                        @click="toggleSaleForm"
                        class="w-full rounded-md bg-green-600 px-6 py-3 text-lg font-semibold text-white shadow-sm hover:bg-green-500"
                    >
                        {{ showSaleForm ? '✕ Yopish' : '🛒 Savdo qilish' }}
                    </button>
                </div>

                <!-- Savdo qilish formasi -->
                <div v-if="showSaleForm" class="mb-6 overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                            Yangi servis va mahsulot sotish
                        </h3>
                    </div>
                    <div class="p-6">
                        <form @submit.prevent="submitService">
                            <!-- Servis ma'lumotlari -->
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
                                        Keyingi servis (km) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="serviceForm.next_service_km"
                                        type="number"
                                        required
                                        min="1000"
                                        placeholder="10000"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Servis turi <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="serviceForm.service_type"
                                        type="text"
                                        required
                                        placeholder="Moy almashtirish"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Ish haqi (so'm)
                                    </label>
                                    <input
                                        v-model="serviceForm.labor_cost"
                                        type="number"
                                        min="0"
                                        placeholder="50000"
                                        @input="updateTotalCost"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Jami narx (so'm)
                                    </label>
                                    <input
                                        v-model="serviceForm.cost"
                                        type="number"
                                        readonly
                                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm dark:border-gray-600 dark:bg-gray-600 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- Mahsulot qo'shish -->
                            <div class="mb-6">
                                <h4 class="mb-3 text-md font-semibold text-gray-900 dark:text-white">
                                    Mahsulotlar qo'shish
                                </h4>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Mahsulot tanlang
                                        </label>
                                        <Multiselect
                                            v-model="selectedProductId"
                                            :options="productOptions"
                                            :searchable="true"
                                            placeholder="Mahsulot qidirish..."
                                            noOptionsText="Mahsulot topilmadi"
                                            noResultsText="Natija topilmadi"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Miqdor
                                        </label>
                                        <div class="mt-1 flex gap-2">
                                            <input
                                                v-model="selectedQuantity"
                                                type="number"
                                                min="1"
                                                step="0.1"
                                                placeholder="1"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            />
                                            <button
                                                type="button"
                                                @click="addToCart"
                                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                                            >
                                                + Qo'shish
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Savatcha -->
                            <div v-if="cart.length > 0" class="mb-6">
                                <h4 class="mb-3 text-md font-semibold text-gray-900 dark:text-white">
                                    Savatcha ({{ cart.length }} ta mahsulot)
                                </h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">
                                                    Mahsulot
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">
                                                    Miqdor
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">
                                                    Narx
                                                </th>
                                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">
                                                    Jami
                                                </th>
                                                <th class="px-4 py-3"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                            <tr v-for="(item, index) in cart" :key="item.id">
                                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900 dark:text-white">
                                                    {{ item.name }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900 dark:text-white">
                                                    {{ item.quantity }} {{ item.unit }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-900 dark:text-white">
                                                    {{ item.unit_price.toLocaleString() }} so'm
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ item.total_price.toLocaleString() }} so'm
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-4 text-sm">
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

                            <!-- Saqlash tugmasi -->
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
                                    <Link
                                        :href="route('service-logs.create', { vehicle_id: vehicle.id })"
                                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                    >
                                        + Servis Qo'shish
                                    </Link>
                                </div>
                            </div>
                            <div class="p-6">
                                <div v-if="vehicle.service_logs && vehicle.service_logs.length > 0" class="space-y-4">
                                    <div
                                        v-for="log in vehicle.service_logs"
                                        :key="log.id"
                                        class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3">
                                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                                        {{ log.service_type }}
                                                    </h4>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ new Date(log.service_date).toLocaleDateString('uz-UZ') }}
                                                    </span>
                                                </div>
                                                <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-gray-500 dark:text-gray-400">Probeg:</span>
                                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                                            {{ log.odometer_reading.toLocaleString() }} km
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-500 dark:text-gray-400">Keyingi servis:</span>
                                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                                            {{ (log.odometer_reading + log.next_service_km).toLocaleString() }} km
                                                        </span>
                                                    </div>
                                                    <div v-if="log.cost">
                                                        <span class="text-gray-500 dark:text-gray-400">Narxi:</span>
                                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                                            {{ Number(log.cost).toLocaleString() }} so'm
                                                        </span>
                                                    </div>
                                                </div>
                                                <p v-if="log.notes" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                                    {{ log.notes }}
                                                </p>
                                            </div>
                                            <Link
                                                :href="route('service-logs.show', log.id)"
                                                class="ml-4 rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200"
                                            >
                                                Batafsil
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Servis yozuvlari yo'q
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Birinchi servis yozuvini qo'shing
                                    </p>
                                    <div class="mt-6">
                                        <Link
                                            :href="route('service-logs.create', { vehicle_id: vehicle.id })"
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                        >
                                            + Servis Qo'shish
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
