<script setup>
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    products: Array,
    saleTotal: Number,
    profit: Number,
    reminders: Array,
});

const form = useForm({});

const finish = () => {
    form.post(route('onboarding.finish'));
};
</script>

<template>
    <Head title="Natijalar" />

    <OnboardingLayout :step="4">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
            Tabriklaymiz! Birinchi savdongiz yakunlandi
        </h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Quyida natijalarni ko'rishingiz mumkin.
        </p>

        <div class="mb-4 rounded-md bg-gray-50 p-3 dark:bg-gray-900">
            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Mahsulotlar qoldig'i</h3>
            <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <li v-for="product in products" :key="product.id" class="flex justify-between">
                    <span>{{ product.name }}</span>
                    <span>{{ product.stock_quantity }} {{ product.unit }}</span>
                </li>
            </ul>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-2">
            <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-900">
                <p class="text-xs text-gray-500 dark:text-gray-400">Savdo summasi</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ saleTotal.toLocaleString() }} so'm</p>
            </div>
            <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-900">
                <p class="text-xs text-gray-500 dark:text-gray-400">Foyda</p>
                <p class="text-lg font-semibold text-green-600">{{ profit.toLocaleString() }} so'm</p>
            </div>
        </div>

        <div class="mb-4 rounded-md bg-gray-50 p-3 dark:bg-gray-900">
            <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">Mijozga eslatma yuboriladigan sanalar</h3>
            <ul v-if="reminders.length" class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <li v-for="(date, index) in reminders" :key="index">{{ date }}</li>
            </ul>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">Eslatma rejalashtirilmagan.</p>
        </div>

        <p class="mb-4 text-xs text-gray-400 dark:text-gray-500">
            Diqqat: yuqoridagi mahsulot, savdo va eslatma — sinov uchun yaratilgan ma'lumotlar.
            Davom etsangiz, ular o'chiriladi va tizim 0 dan boshlanadi.
        </p>

        <button
            type="button"
            :disabled="form.processing"
            @click="finish"
            class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
        >
            Tizimdan foydalanishni o'rganishni tugatish
        </button>
    </OnboardingLayout>
</template>
