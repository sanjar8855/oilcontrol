<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    vehicle: Object,
    clients: Array,
});

const form = useForm({
    client_id: props.vehicle.client_id,
    make: props.vehicle.make,
    model: props.vehicle.model || '',
    year: props.vehicle.year || '',
    plate_number: props.vehicle.plate_number || '',
    vin: props.vehicle.vin || '',
});

const submit = () => {
    form.put(route('vehicles.update', props.vehicle.id));
};
</script>

<template>
    <Head title="Avtomobilni Tahrirlash" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ vehicle.make }} {{ vehicle.model }} ni Tahrirlash
                </h2>
                <Link
                    :href="route('vehicles.show', vehicle.id)"
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
                            <!-- Mijoz tanlash -->
                            <div>
                                <InputLabel for="client_id" value="Mijoz *" />
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Mijozni tanlang</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">
                                        {{ client.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.client_id" />
                            </div>

                            <!-- Marka (Make) -->
                            <div>
                                <InputLabel for="make" value="Mashina markasi *" />
                                <TextInput
                                    id="make"
                                    v-model="form.make"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.make" />
                            </div>

                            <!-- Model -->
                            <div>
                                <InputLabel for="model" value="Model (ixtiyoriy)" />
                                <TextInput
                                    id="model"
                                    v-model="form.model"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.model" />
                            </div>

                            <!-- Yil -->
                            <div>
                                <InputLabel for="year" value="Ishlab chiqarilgan yili" />
                                <TextInput
                                    id="year"
                                    v-model="form.year"
                                    type="number"
                                    class="mt-1 block w-full"
                                    :min="1900"
                                    :max="new Date().getFullYear() + 1"
                                />
                                <InputError class="mt-2" :message="form.errors.year" />
                            </div>

                            <!-- Davlat raqami -->
                            <div>
                                <InputLabel for="plate_number" value="Davlat raqami" />
                                <TextInput
                                    id="plate_number"
                                    v-model="form.plate_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.plate_number" />
                            </div>

                            <!-- VIN -->
                            <div>
                                <InputLabel for="vin" value="VIN kodi (ixtiyoriy)" />
                                <TextInput
                                    id="vin"
                                    v-model="form.vin"
                                    type="text"
                                    class="mt-1 block w-full"
                                    maxlength="50"
                                />
                                <InputError class="mt-2" :message="form.errors.vin" />
                            </div>

                            <!-- Tugmalar -->
                            <div class="flex items-center justify-end gap-4">
                                <Link
                                    :href="route('vehicles.show', vehicle.id)"
                                    class="rounded-md px-4 py-2 text-sm font-semibold text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                >
                                    Bekor qilish
                                </Link>
                                <PrimaryButton :disabled="form.processing">
                                    Yangilash
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
