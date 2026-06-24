<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    vehicles: Array,
    products: Array,
    selectedVehicleId: [String, Number],
});

const form = useForm({
    vehicle_id: props.selectedVehicleId || '',
    service_date: new Date().toISOString().split('T')[0],
    odometer_reading: '',
    next_service_km: 5000,
    avg_monthly_km: '',
    service_type: 'oil_change',
    cost: '',
    labor_cost: '',
    notes: '',
    products: [],
});

const selectedProducts = ref([]);

const addProduct = () => {
    selectedProducts.value.push({
        id: null,
        quantity: 1,
        unit_price: 0,
    });
};

const removeProduct = (index) => {
    selectedProducts.value.splice(index, 1);
};

const getProduct = (productId) => {
    return props.products.find(p => p.id === productId);
};

const updateProductPrice = (index) => {
    const selected = selectedProducts.value[index];
    if (selected.id) {
        const product = getProduct(selected.id);
        if (product) {
            selected.unit_price = product.selling_price;
        }
    }
};

const calculateProductTotal = (item) => {
    return (item.quantity || 0) * (item.unit_price || 0);
};

const productsTotal = computed(() => {
    return selectedProducts.value.reduce((sum, item) => {
        return sum + calculateProductTotal(item);
    }, 0);
});

const laborCostValue = computed(() => {
    return parseFloat(form.labor_cost) || 0;
});

const grandTotal = computed(() => {
    return productsTotal.value + laborCostValue.value;
});

const submit = () => {
    // Mahsulotlarni formaga qo'shish
    form.products = selectedProducts.value.filter(p => p.id !== null);
    form.cost = grandTotal.value; // Jami summani saqlash
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
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Avtomobil tanlash -->
                            <div>
                                <InputLabel for="vehicle_id" value="Avtomobil *" />
                                <select
                                    id="vehicle_id"
                                    v-model="form.vehicle_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Avtomobilni tanlang</option>
                                    <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                                        {{ vehicle.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.vehicle_id" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Servis sanasi -->
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

                                <!-- Servis turi -->
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
                                <!-- Probeg (Odometer) -->
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

                                <!-- Keyingi servis km -->
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

                                <!-- O'rtacha oylik km -->
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

                            <!-- Ishlatilgan mahsulotlar -->
                            <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                        Ishlatilgan mahsulotlar
                                    </h3>
                                    <button
                                        type="button"
                                        @click="addProduct"
                                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                    >
                                        + Mahsulot qo'shish
                                    </button>
                                </div>

                                <div v-if="selectedProducts.length > 0" class="space-y-3">
                                    <div
                                        v-for="(item, index) in selectedProducts"
                                        :key="index"
                                        class="grid grid-cols-12 gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-900"
                                    >
                                        <!-- Mahsulot -->
                                        <div class="col-span-5">
                                            <select
                                                v-model="item.id"
                                                @change="updateProductPrice(index)"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                                required
                                            >
                                                <option :value="null">Mahsulot tanlang</option>
                                                <option v-for="product in products" :key="product.id" :value="product.id">
                                                    {{ product.name }} ({{ product.stock_quantity }} {{ product.unit }})
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Miqdor -->
                                        <div class="col-span-2">
                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                placeholder="Miqdor"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                                required
                                            />
                                        </div>

                                        <!-- Narxi -->
                                        <div class="col-span-2">
                                            <input
                                                v-model.number="item.unit_price"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                placeholder="Narxi"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                                required
                                            />
                                        </div>

                                        <!-- Jami -->
                                        <div class="col-span-2 flex items-center">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ calculateProductTotal(item).toLocaleString() }}
                                            </span>
                                        </div>

                                        <!-- O'chirish -->
                                        <div class="col-span-1 flex items-center justify-end">
                                            <button
                                                type="button"
                                                @click="removeProduct(index)"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Xizmat haqqilari -->
                            <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
                                <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">
                                    Xizmat haqqi
                                </h3>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <InputLabel for="labor_cost" value="Ish haqi" />
                                        <TextInput
                                            id="labor_cost"
                                            v-model="form.labor_cost"
                                            type="number"
                                            class="mt-1 block w-full"
                                            min="0"
                                            step="0.01"
                                            placeholder="50000"
                                        />
                                        <InputError class="mt-2" :message="form.errors.labor_cost" />
                                    </div>

                                    <div class="flex flex-col justify-end">
                                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">Mahsulotlar:</span>
                                                <span class="font-medium text-gray-900 dark:text-white">
                                                    {{ productsTotal.toLocaleString() }} so'm
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

                            <!-- Eslatmalar -->
                            <div>
                                <InputLabel for="notes" value="Eslatmalar (ixtiyoriy)" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="4"
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
                                <PrimaryButton :disabled="form.processing">
                                    Saqlash
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
