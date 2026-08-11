<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watchEffect } from 'vue';
import QRCode from 'qrcode';

const props = defineProps({
    client: Object,
    telegramBotUsername: String,
});

const showConnectModal = ref(false);
const qrDataUrl = ref(null);
const copied = ref(false);

const deepLink = computed(() => {
    if (!props.telegramBotUsername || !props.client.telegram_link_token) return null;
    return `https://t.me/${props.telegramBotUsername}?start=${props.client.telegram_link_token}`;
});

watchEffect(async () => {
    if (deepLink.value && showConnectModal.value) {
        qrDataUrl.value = await QRCode.toDataURL(deepLink.value, { width: 240, margin: 1 });
    }
});

const copyLink = async () => {
    if (!deepLink.value) return;
    await navigator.clipboard.writeText(deepLink.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const unlinkTelegram = () => {
    if (!confirm('Telegram bog\'lanishini uzishga ishonchingiz komilmi?')) return;
    router.post(route('clients.telegram-unlink', props.client.id), {}, { preserveScroll: true });
};

const regenerateLink = () => {
    router.post(route('clients.telegram-regenerate-link', props.client.id), {}, { preserveScroll: true });
};
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
                        O'zgartirish
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

        <div class="py-4 sm:py-6">
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

                        <!-- Telegram bog'lanishi -->
                        <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                            <div class="border-b border-gray-200 bg-white px-4 py-5 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                    Telegram
                                </h3>
                            </div>
                            <div class="p-6">
                                <div v-if="client.telegram_id" class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800 dark:bg-green-800 dark:text-green-100">
                                        ✓ Ulangan
                                    </span>
                                    <button
                                        @click="unlinkTelegram"
                                        class="text-sm font-medium text-red-600 hover:text-red-500 dark:text-red-400"
                                    >
                                        Uzish
                                    </button>
                                </div>
                                <div v-else>
                                    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                                        Mijoz botga ulanmagan — eslatma va kvitansiya yubora olmaysiz.
                                    </p>
                                    <button
                                        @click="showConnectModal = true"
                                        class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500"
                                    >
                                        📱 Botga ulash
                                    </button>
                                </div>
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

        <!-- Botga ulash modali -->
        <div
            v-if="showConnectModal"
            @click.self="showConnectModal = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Botga ulash</h3>
                    <button @click="showConnectModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">✕</button>
                </div>

                <template v-if="!telegramBotUsername">
                    <p class="text-sm text-red-600 dark:text-red-400">
                        Bot username sozlanmagan (TELEGRAM_BOT_USERNAME). Administratorga murojaat qiling.
                    </p>
                </template>
                <template v-else>
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Mijozning telefonini oling va QR'ni skanerlating — yoki havolani nusxalab yuboring.
                    </p>

                    <div class="mb-4 flex justify-center">
                        <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR" class="rounded-lg border border-gray-200 dark:border-gray-700" />
                        <div v-else class="flex h-60 w-60 items-center justify-center text-sm text-gray-400">Yuklanmoqda...</div>
                    </div>

                    <div class="mb-3 flex items-center gap-2">
                        <input
                            :value="deepLink"
                            readonly
                            class="block w-full truncate rounded-md border-gray-300 bg-gray-50 text-xs shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                        />
                        <button
                            @click="copyLink"
                            class="shrink-0 rounded-md bg-gray-700 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-600"
                        >
                            {{ copied ? 'Nusxalandi ✓' : 'Nusxalash' }}
                        </button>
                    </div>

                    <button
                        @click="regenerateLink"
                        class="text-xs text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        Havolani yangilash (eskisi ishlamay qoladi)
                    </button>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
