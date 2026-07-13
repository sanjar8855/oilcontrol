<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    serviceLogs: Object,
});

const deleteLog = (log) => {
    if (confirm(`Haqiqatan ham bu servis yozuvini o'chirmoqchimisiz?`)) {
        router.delete(route('service-logs.destroy', log.id));
    }
};
</script>

<template>
    <Head title="Servis Yozuvlari" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Servis Yozuvlari
                </h2>
                <Link
                    :href="route('service-logs.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi Servis
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div v-if="serviceLogs.data.length > 0" class="space-y-4">
                            <div
                                v-for="log in serviceLogs.data"
                                :key="log.id"
                                class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4">
                                            <Link
                                                :href="route('vehicles.show', log.vehicle.id)"
                                                class="text-lg font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                            >
                                                {{ log.vehicle.make }} {{ log.vehicle.model }}
                                            </Link>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ new Date(log.service_date).toLocaleDateString('uz-UZ') }}
                                            </span>
                                        </div>
                                        <Link
                                            :href="route('clients.show', log.vehicle.client.id)"
                                            class="mt-1 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400"
                                        >
                                            Egasi: {{ log.vehicle.client.name }}
                                        </Link>

                                        <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
                                            <div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Servis turi:</span>
                                                <p class="font-medium text-gray-900 dark:text-white">
                                                    {{ log.service_type }}
                                                </p>
                                            </div>
                                            <div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Probeg:</span>
                                                <p class="font-medium text-gray-900 dark:text-white">
                                                    {{ log.odometer_reading.toLocaleString() }} km
                                                </p>
                                            </div>
                                            <div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Keyingi servis:</span>
                                                <p class="font-medium text-gray-900 dark:text-white">
                                                    {{ (log.odometer_reading + log.next_service_km).toLocaleString() }} km
                                                </p>
                                            </div>
                                            <div v-if="log.total_amount">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Narxi:</span>
                                                <p class="font-medium text-gray-900 dark:text-white">
                                                    {{ Number(log.total_amount).toLocaleString() }} so'm
                                                </p>
                                            </div>
                                        </div>

                                        <p v-if="log.notes" class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ log.notes }}
                                        </p>
                                    </div>

                                    <div class="ml-4 flex flex-col gap-2">
                                        <Link
                                            :href="route('service-logs.show', log.id)"
                                            class="rounded-md bg-indigo-100 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900 dark:text-indigo-300"
                                        >
                                            Ko'rish
                                        </Link>
                                        <button
                                            @click="deleteLog(log)"
                                            class="rounded-md bg-red-100 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-200 dark:bg-red-900 dark:text-red-300"
                                        >
                                            O'chirish
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div v-if="serviceLogs.links.length > 3" class="mt-6 flex justify-center">
                                <nav class="inline-flex -space-x-px rounded-md shadow-sm">
                                    <component
                                        :is="link.url ? Link : 'span'"
                                        v-for="(link, index) in serviceLogs.links"
                                        :key="index"
                                        :href="link.url"
                                        :class="[
                                            'px-4 py-2 text-sm',
                                            link.active
                                                ? 'z-10 bg-indigo-600 text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300',
                                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === serviceLogs.links.length - 1 ? 'rounded-r-md' : '',
                                        ]"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>

                        <!-- Bo'sh holat -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                Hali servis yozuvlari yo'q
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Birinchi servis yozuvini qo'shishdan boshlang
                            </p>
                            <div class="mt-6">
                                <Link
                                    :href="route('service-logs.create')"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                >
                                    + Servis qo'shish
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
