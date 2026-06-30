<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    serviceLog: Object,
    vehicles: Array,
});

const form = useForm({
    vehicle_id: props.serviceLog.vehicle_id,
    service_date: props.serviceLog.service_date,
    odometer_reading: props.serviceLog.odometer_reading,
    next_service_km: props.serviceLog.next_service_km,
    service_type: props.serviceLog.service_type || 'Servis',
    notes: props.serviceLog.notes || '',
});

const submit = () => {
    form.put(route('service-logs.update', props.serviceLog.id));
};
</script>

<template>
    <Head title="Servisni Tahrirlash" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Servis Yozuvini Tahrirlash
                </h2>
                <Link
                    :href="route('service-logs.show', serviceLog.id)"
                    class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                >
                    ← Orqaga
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-2xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">

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

                            <!-- Probeg -->
                            <div>
                                <InputLabel for="odometer_reading" value="Probeg (km) *" />
                                <TextInput
                                    id="odometer_reading"
                                    v-model="form.odometer_reading"
                                    type="number"
                                    class="mt-1 block w-full"
                                    required
                                    min="0"
                                    placeholder="125000"
                                />
                                <InputError class="mt-2" :message="form.errors.odometer_reading" />
                            </div>

                            <!-- Nechi km keyin servis -->
                            <div>
                                <InputLabel for="next_service_km" value="Nechi km keyin servis *" />
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
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Keyingi servis: {{ (parseInt(form.odometer_reading) + parseInt(form.next_service_km) || 0).toLocaleString() }} km
                                </p>
                                <InputError class="mt-2" :message="form.errors.next_service_km" />
                            </div>

                            <!-- Izoh -->
                            <div>
                                <InputLabel for="notes" value="Izoh (ixtiyoriy)" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                                    placeholder="Qo'shimcha ma'lumot..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.notes" />
                            </div>

                            <!-- Tugmalar -->
                            <div class="flex items-center justify-end gap-4">
                                <Link
                                    :href="route('service-logs.show', serviceLog.id)"
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
