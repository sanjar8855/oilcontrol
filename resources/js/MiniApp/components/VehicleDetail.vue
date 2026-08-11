<script setup>
import { onMounted, ref } from 'vue';
import api from '../api';

const props = defineProps({
    vehicleId: Number,
});

defineEmits(['back']);

const loading = ref(true);
const error = ref(null);
const data = ref(null);

const formatMoney = (n) => new Intl.NumberFormat('uz-UZ').format(n || 0) + " so'm";
const formatDate = (d) => (d ? new Date(d).toLocaleDateString('uz-UZ') : '-');
const formatKm = (n) => (n === null || n === undefined ? '-' : new Intl.NumberFormat('uz-UZ').format(n) + ' km');

const statusLabel = {
    paid: { text: 'To\'liq to\'langan', class: 'bg-green-100 text-green-700' },
    partial: { text: 'Qisman to\'langan', class: 'bg-yellow-100 text-yellow-800' },
    unpaid: { text: 'To\'lanmagan', class: 'bg-red-100 text-red-700' },
};

onMounted(async () => {
    try {
        const res = await api.get(`/vehicles/${props.vehicleId}`);
        data.value = res.data;
    } catch (e) {
        error.value = 'Ma\'lumotni yuklab bo\'lmadi';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="p-4">
        <button @click="$emit('back')" class="mb-4 text-sm font-medium text-blue-600">← Garajga qaytish</button>

        <div v-if="loading" class="py-16 text-center text-gray-400">Yuklanmoqda...</div>
        <div v-else-if="error" class="py-16 text-center text-red-500">{{ error }}</div>

        <template v-else-if="data">
            <h2 class="text-lg font-bold text-gray-900">{{ data.vehicle.make }} {{ data.vehicle.model }}</h2>
            <p class="text-sm text-gray-500">{{ data.vehicle.plate_number }} · {{ data.workshop_name }}</p>

            <h3 class="mb-2 mt-6 text-sm font-semibold uppercase text-gray-400">Kvitansiyalar</h3>

            <div class="space-y-3">
                <div v-for="log in data.history" :key="log.id" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="font-medium text-gray-900">{{ formatDate(log.service_date) }}</div>
                        <span :class="statusLabel[log.payment_status]?.class" class="rounded-full px-2 py-0.5 text-xs font-semibold">
                            {{ statusLabel[log.payment_status]?.text || log.payment_status }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">{{ log.service_type }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <div class="text-gray-400">Probeg</div>
                            <div class="font-medium text-gray-800">{{ formatKm(log.odometer_reading) }}</div>
                        </div>
                        <div>
                            <div class="text-gray-400">Keyingi servis</div>
                            <div class="font-medium text-gray-800">{{ formatKm(log.next_service_km) }}</div>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2 text-sm">
                        <span class="text-gray-500">Jami: {{ formatMoney(log.total_amount) }}</span>
                        <span v-if="log.remaining_amount > 0" class="font-semibold text-red-600">
                            Qarz: {{ formatMoney(log.remaining_amount) }}
                        </span>
                    </div>
                </div>

                <div v-if="!data.history.length" class="py-8 text-center text-gray-400">Servis tarixi yo'q</div>
            </div>
        </template>
    </div>
</template>
