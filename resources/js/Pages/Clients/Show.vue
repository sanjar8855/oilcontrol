<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    client: Object,
});
</script>

<template>
    <Head :title="client.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ client.name }}
                </h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('clients.edit', client.id)"
                        class="rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500"
                    >
                        Tahrirlash
                    </Link>
                    <Link
                        :href="route('clients.index')"
                        class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        ← Orqaga
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Mijoz ma'lumotlari -->
                    <div class="lg:col-span-1">
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                            <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                    Mijoz Ma'lumotlari
                                </h3>
                            </div>
                            <div class="p-6">
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ismi</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ client.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefon</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ client.phone }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ client.email || '-' }}</dd>
                                    </div>
                                    <div v-if="client.notes">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Eslatmalar</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ client.notes }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Avtomobillar ro'yxati -->
                    <div class="lg:col-span-2">
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                            <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                        Avtomobillar
                                    </h3>
                                    <Link
                                        :href="route('vehicles.create', { client_id: client.id })"
                                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                    >
                                        + Avtomobil Qo'shish
                                    </Link>
                                </div>
                            </div>
                            <div class="p-6">
                                <div v-if="client.vehicles && client.vehicles.length > 0" class="space-y-4">
                                    <div
                                        v-for="vehicle in client.vehicles"
                                        :key="vehicle.id"
                                        class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                                    {{ vehicle.make }} {{ vehicle.model }}
                                                </h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ vehicle.year }} | {{ vehicle.plate_number || 'Nomer yo\'q' }}
                                                </p>
                                                <p v-if="vehicle.service_logs && vehicle.service_logs.length > 0" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                                    Oxirgi servis: {{ new Date(vehicle.service_logs[0].service_date).toLocaleDateString('uz-UZ') }}
                                                </p>
                                            </div>
                                            <Link
                                                :href="route('vehicles.show', vehicle.id)"
                                                class="rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200"
                                            >
                                                Ko'rish
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Avtomobillar yo'q
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Ushbu mijoz uchun birinchi avtomobilni qo'shing
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
