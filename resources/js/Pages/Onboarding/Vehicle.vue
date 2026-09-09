<!-- resources/js/Pages/Onboarding/Vehicle.vue -->
<script setup>
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    phone: '',
    plate_number: '',
    make: '',
});

const submit = () => {
    form.post(route('onboarding.vehicle.store'));
};
</script>

<template>
    <Head title="Mijoz va moshina qo'shish" />

    <OnboardingLayout :step="2">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
            Mijoz va moshina qo'shing
        </h2>
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Birinchi mijozingizni va uning moshinasini kiriting.
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mijoz ismi</label>
                <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefon</label>
                <input v-model="form.phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Davlat raqami</label>
                <input v-model="form.plate_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                <p v-if="form.errors.plate_number" class="mt-1 text-sm text-red-600">{{ form.errors.plate_number }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Moshina modeli</label>
                <input v-model="form.make" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                <p v-if="form.errors.make" class="mt-1 text-sm text-red-600">{{ form.errors.make }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Davom etish
            </button>
        </form>
    </OnboardingLayout>
</template>
