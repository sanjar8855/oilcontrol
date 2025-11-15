<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    vehicles: Array,
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
    notes: '',
});

const submit = () => {
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

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
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
                                    placeholder="Masalan: 85000"
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
                                    placeholder="Masalan: 5000"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Necha km dan keyin keyingi servis kerak bo'ladi
                                </p>
                                <InputError class="mt-2" :message="form.errors.next_service_km" />
                            </div>

                            <!-- O'rtacha oylik km -->
                            <div>
                                <InputLabel for="avg_monthly_km" value="O'rtacha oyiga qancha km yuradi?" />
                                <TextInput
                                    id="avg_monthly_km"
                                    v-model="form.avg_monthly_km"
                                    type="number"
                                    class="mt-1 block w-full"
                                    min="0"
                                    placeholder="Masalan: 1000"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Eslatma vaqtini hisoblash uchun (ixtiyoriy)
                                </p>
                                <InputError class="mt-2" :message="form.errors.avg_monthly_km" />
                            </div>

                            <!-- Narxi -->
                            <div>
                                <InputLabel for="cost" value="Xizmat narxi (so'm)" />
                                <TextInput
                                    id="cost"
                                    v-model="form.cost"
                                    type="number"
                                    class="mt-1 block w-full"
                                    min="0"
                                    step="0.01"
                                    placeholder="Masalan: 250000"
                                />
                                <InputError class="mt-2" :message="form.errors.cost" />
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
