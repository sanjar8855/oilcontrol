<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    serviceLog: Object,
});

const productsTotal = () => {
    if (!props.serviceLog.products?.length) return 0;
    return props.serviceLog.products.reduce((sum, p) => sum + Number(p.pivot.total_price), 0);
};
</script>

<template>
    <Head title="Servis Tafsilotlari" />

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
            <div class="mx-auto max-w-3xl px-3 sm:px-6 lg:px-8 space-y-4">

                <!-- Asosiy ma'lumotlar -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ new Date(serviceLog.service_date).toLocaleDateString('uz-UZ', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                                </p>
                                <div class="mt-1 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                    <Link :href="route('vehicles.show', serviceLog.vehicle.id)" class="text-indigo-600 hover:underline dark:text-indigo-400">
                                        {{ serviceLog.vehicle.make }} {{ serviceLog.vehicle.model }}
                                    </Link>
                                    <span>·</span>
                                    <Link :href="route('clients.show', serviceLog.vehicle.client.id)" class="hover:underline">
                                        {{ serviceLog.vehicle.client.name }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 divide-x divide-gray-100 dark:divide-gray-700">
                        <div class="px-6 py-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Probeg</p>
                            <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-white">
                                {{ serviceLog.odometer_reading.toLocaleString() }} km
                            </p>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Keyingi servis</p>
                            <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-white">
                                {{ (serviceLog.odometer_reading + serviceLog.next_service_km).toLocaleString() }} km
                            </p>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Jami savdo</p>
                            <p class="mt-0.5 text-base font-semibold text-indigo-600 dark:text-indigo-400">
                                {{ Number(serviceLog.total_amount || 0).toLocaleString() }} so'm
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sotilgan mahsulotlar -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-3 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sotilgan mahsulotlar</h3>
                    </div>

                    <div v-if="serviceLog.products && serviceLog.products.length > 0">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50">
                                    <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Mahsulot</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Miqdor</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Narx</th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Jami</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="product in serviceLog.products" :key="product.id">
                                    <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">{{ product.name }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">
                                        {{ product.pivot.quantity }} {{ product.unit }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">
                                        {{ Number(product.pivot.unit_price).toLocaleString() }} so'm
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">
                                        {{ Number(product.pivot.total_price).toLocaleString() }} so'm
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="serviceLog.labor_cost > 0">
                                <tr class="bg-gray-50 dark:bg-gray-700/50">
                                    <td colspan="3" class="px-6 py-2 text-right text-xs text-gray-500 dark:text-gray-400">Qo'shimcha xizmatlar</td>
                                    <td class="px-6 py-2 text-right text-sm font-medium text-gray-900 dark:text-white">
                                        {{ Number(serviceLog.labor_cost).toLocaleString() }} so'm
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div v-else class="px-6 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
                        Mahsulot ma'lumoti yo'q
                    </div>
                </div>

                <!-- Izoh -->
                <div v-if="serviceLog.notes" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="px-6 py-4">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Izoh</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ serviceLog.notes }}</p>
                    </div>
                </div>

                <!-- Eslatmalar -->
                <div v-if="serviceLog.reminders && serviceLog.reminders.length > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-3 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Eslatmalar</h3>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div
                            v-for="reminder in serviceLog.reminders"
                            :key="reminder.id"
                            class="flex items-center justify-between px-6 py-3"
                        >
                            <div>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    {{ new Date(reminder.scheduled_date).toLocaleDateString('uz-UZ') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ reminder.notification_type }}</p>
                            </div>
                            <span :class="[
                                'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                reminder.status === 'sent'    ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' :
                                reminder.status === 'pending' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' :
                                                                'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                            ]">
                                {{ reminder.status }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
