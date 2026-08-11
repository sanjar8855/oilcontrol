<script setup>
import { onMounted, ref } from 'vue';
import api, { tg } from './api';
import GarageView from './components/GarageView.vue';
import VehicleDetail from './components/VehicleDetail.vue';

const loading = ref(true);
const error = ref(null);
const clients = ref([]);
const openVehicleId = ref(null);

onMounted(async () => {
    if (tg) {
        tg.ready();
        tg.expand();
    }

    if (!tg?.initData) {
        loading.value = false;
        error.value = 'Bu sahifa faqat Telegram bot ichida ochiladi.';
        return;
    }

    try {
        const res = await api.get('/garage');
        clients.value = res.data.clients;
    } catch (e) {
        error.value = 'Bog\'lanishda xatolik yuz berdi. Qaytadan urinib ko\'ring.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <header class="sticky top-0 z-10 border-b border-gray-200 bg-white px-4 py-3">
            <h1 class="text-lg font-bold text-gray-900">🚗 Mening garajim</h1>
        </header>

        <div v-if="loading" class="py-20 text-center text-gray-400">Yuklanmoqda...</div>

        <div v-else-if="error" class="px-4 py-16 text-center text-gray-500">
            {{ error }}
        </div>

        <VehicleDetail
            v-else-if="openVehicleId"
            :vehicle-id="openVehicleId"
            @back="openVehicleId = null"
        />

        <GarageView
            v-else
            :clients="clients"
            @open-vehicle="openVehicleId = $event.id"
        />
    </div>
</template>
