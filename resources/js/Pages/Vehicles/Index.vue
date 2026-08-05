<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    vehicles: Object,
});

const deleteVehicle = (vehicle) => {
    if (confirm(`Haqiqatan ham ${vehicle.make} ${vehicle.model} ni o'chirmoqchimisiz?`)) {
        router.delete(route('vehicles.destroy', vehicle.id));
    }
};
</script>

<template>
    <Head title="Avtomobillar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Avtomobillar
                </h2>
                <Link
                    :href="route('vehicles.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi Avtomobil
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div v-if="vehicles.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Avtomobil
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Egasi
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Davlat raqami
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Oxirgi servis
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Amallar
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="vehicle in vehicles.data" :key="vehicle.id">
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ vehicle.make }} {{ vehicle.model }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ vehicle.year || '-' }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <Link
                                                :href="route('clients.show', vehicle.client.id)"
                                                class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                            >
                                                {{ vehicle.client.name }}
                                            </Link>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-gray-300">
                                                {{ vehicle.plate_number || '-' }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div v-if="vehicle.latest_service" class="text-sm text-gray-900 dark:text-gray-300">
                                                {{ new Date(vehicle.latest_service.service_date).toLocaleDateString('uz-UZ') }}
                                            </div>
                                            <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                                                Servis yo'q
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Link
                                                :href="route('vehicles.show', vehicle.id)"
                                                class="mr-3 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                Ko'rish
                                            </Link>
                                            <Link
                                                :href="route('vehicles.edit', vehicle.id)"
                                                class="mr-3 text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300"
                                            >
                                                Tahrirlash
                                            </Link>
                                            <button
                                                @click="deleteVehicle(vehicle)"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                O'chirish
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div v-if="vehicles.links.length > 3" class="mt-6 flex justify-center">
                                <nav class="inline-flex -space-x-px rounded-md shadow-sm">
                                    <component
                                        :is="link.url ? Link : 'span'"
                                        v-for="(link, index) in vehicles.links"
                                        :key="index"
                                        :href="link.url"
                                        :class="[
                                            'px-4 py-2 text-sm',
                                            link.active
                                                ? 'z-10 bg-indigo-600 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300',
                                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === vehicles.links.length - 1 ? 'rounded-r-md' : '',
                                        ]"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>

                        <!-- Bo'sh holat -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                Hali avtomobillar yo'q
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Birinchi avtomobilni qo'shishdan boshlang
                            </p>
                            <div class="mt-6">
                                <Link
                                    :href="route('vehicles.create')"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                >
                                    + Avtomobil qo'shish
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
