<script setup>
defineProps({
    vehicle: Object,
    workshopName: String,
});

defineEmits(['open']);

const formatKm = (n) => (n === null || n === undefined ? '-' : new Intl.NumberFormat('uz-UZ').format(n) + ' km');

const dueLabel = (vehicle) => {
    if (vehicle.days_remaining === null) return null;
    if (vehicle.days_remaining < 0) return { text: `${Math.abs(vehicle.days_remaining)} kun kechikdi`, tone: 'danger' };
    if (vehicle.days_remaining <= 7) return { text: `${vehicle.days_remaining} kundan so'ng`, tone: 'warning' };
    return { text: `${vehicle.days_remaining} kundan so'ng`, tone: 'ok' };
};
</script>

<template>
    <button
        @click="$emit('open', vehicle)"
        class="w-full rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm active:bg-gray-50"
    >
        <div class="flex items-start justify-between">
            <div>
                <div class="font-semibold text-gray-900">{{ vehicle.make }} {{ vehicle.model }}</div>
                <div class="text-sm text-gray-500">{{ vehicle.plate_number || 'Nomer yo\'q' }}</div>
            </div>
            <span v-if="workshopName" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                {{ workshopName }}
            </span>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
            <div>
                <div class="text-gray-400">Oxirgi servis</div>
                <div class="font-medium text-gray-800">{{ formatKm(vehicle.last_service_km) }}</div>
            </div>
            <div>
                <div class="text-gray-400">Keyingi servis</div>
                <div class="font-medium text-gray-800">{{ formatKm(vehicle.next_service_km) }}</div>
            </div>
        </div>

        <div v-if="dueLabel(vehicle)" class="mt-3">
            <span
                :class="{
                    'bg-red-100 text-red-700': dueLabel(vehicle).tone === 'danger',
                    'bg-yellow-100 text-yellow-800': dueLabel(vehicle).tone === 'warning',
                    'bg-green-100 text-green-700': dueLabel(vehicle).tone === 'ok',
                }"
                class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold"
            >
                📅 {{ dueLabel(vehicle).text }}
            </span>
        </div>
    </button>
</template>
