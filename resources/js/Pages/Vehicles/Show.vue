<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    vehicle: Object,
});
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

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
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
