<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    serviceLog: Object,
});
</script>

<template>
    <Head :title="`Servis - ${serviceLog.service_type}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Servis Yozuvi Tafsilotlari
                </h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('service-logs.edit', serviceLog.id)"
                        class="rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500"
                    >
                        Tahrirlash
                    </Link>
                    <Link
                        :href="route('vehicles.show', serviceLog.vehicle.id)"
                        class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        ← Avtomobilga qaytish
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-3xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ serviceLog.service_type }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ new Date(serviceLog.service_date).toLocaleDateString('uz-UZ', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Avtomobil ma'lumotlari -->
                        <div class="mb-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                            <h4 class="mb-3 font-semibold text-gray-900 dark:text-white">
                                Avtomobil Ma'lumotlari
                            </h4>
                            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Avtomobil:</dt>
                                    <dd class="mt-1">
                                        <Link
                                            :href="route('vehicles.show', serviceLog.vehicle.id)"
                                            class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                        >
                                            {{ serviceLog.vehicle.make }} {{ serviceLog.vehicle.model }}
                                        </Link>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Egasi:</dt>
                                    <dd class="mt-1">
                                        <Link
                                            :href="route('clients.show', serviceLog.vehicle.client.id)"
                                            class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                        >
                                            {{ serviceLog.vehicle.client.name }}
                                        </Link>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Servis ma'lumotlari -->
                        <div class="space-y-6">
                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                Servis Ma'lumotlari
                            </h4>

                            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Servis sanasi
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                        {{ new Date(serviceLog.service_date).toLocaleDateString('uz-UZ') }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Servis turi
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                        {{ serviceLog.service_type }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Probeg (km)
                                    </dt>
                                    <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ serviceLog.odometer_reading.toLocaleString() }} km
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Keyingi servis
                                    </dt>
                                    <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ (serviceLog.odometer_reading + serviceLog.next_service_km).toLocaleString() }} km
                                    </dd>
                                </div>

                                <div v-if="serviceLog.avg_monthly_km">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        O'rtacha oylik km
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                        {{ serviceLog.avg_monthly_km.toLocaleString() }} km/oy
                                    </dd>
                                </div>

                            </dl>

                            <div v-if="serviceLog.notes">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Eslatmalar
                                </dt>
                                <dd class="mt-2 rounded-lg bg-gray-50 p-4 text-sm text-gray-900 dark:bg-gray-900 dark:text-white">
                                    {{ serviceLog.notes }}
                                </dd>
                            </div>
                        </div>

                        <!-- Eslatmalar -->
                        <div v-if="serviceLog.reminders && serviceLog.reminders.length > 0" class="mt-8">
                            <h4 class="mb-4 font-semibold text-gray-900 dark:text-white">
                                Eslatmalar
                            </h4>
                            <div class="space-y-3">
                                <div
                                    v-for="reminder in serviceLog.reminders"
                                    :key="reminder.id"
                                    class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                                >
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            {{ new Date(reminder.scheduled_date).toLocaleDateString('uz-UZ') }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ reminder.notification_type }}
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'rounded-full px-3 py-1 text-xs font-medium',
                                            reminder.status === 'sent'
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                : reminder.status === 'pending'
                                                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        ]"
                                    >
                                        {{ reminder.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
