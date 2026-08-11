<script setup>
import VehicleCard from './VehicleCard.vue';

const props = defineProps({
    clients: Array,
});

const emit = defineEmits(['open-vehicle']);

const formatMoney = (n) => new Intl.NumberFormat('uz-UZ').format(n || 0) + " so'm";
</script>

<template>
    <div class="space-y-6 p-4">
        <div v-for="client in clients" :key="client.id" class="space-y-3">
            <div v-if="client.debt > 0" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">
                💳 <span class="font-semibold">{{ client.workshop_name }}</span> — qarz qoldig'i:
                <span class="font-bold">{{ formatMoney(client.debt) }}</span>
            </div>

            <VehicleCard
                v-for="vehicle in client.vehicles"
                :key="vehicle.id"
                :vehicle="vehicle"
                :workshop-name="client.workshop_name"
                @open="emit('open-vehicle', $event)"
            />
        </div>

        <div v-if="!clients.length" class="py-16 text-center text-gray-400">
            <div class="text-4xl">🚗</div>
            <p class="mt-2">Hali avtomobil topilmadi</p>
        </div>
    </div>
</template>
