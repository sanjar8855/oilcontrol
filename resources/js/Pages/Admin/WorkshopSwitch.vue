<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    workshops: Array,
    activeWorkshopId: [Number, null],
});

const switchTo = (workshop) => {
    router.post(route('workshops.switch', workshop.id));
};
</script>

<template>
    <Head title="Workshop tanlash" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Workshop tanlash
            </h2>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-4xl px-3 sm:px-6 lg:px-8">
                <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                    Superadmin sifatida tizimdagi istalgan workshop nomidan kirib, uning ma'lumotlarini ko'rish va boshqarish mumkin. Pastdan birini tanlang.
                </p>

                <div v-if="workshops.length > 0" class="space-y-3">
                    <div
                        v-for="workshop in workshops"
                        :key="workshop.id"
                        class="flex items-center justify-between rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                        :class="{ 'ring-2 ring-indigo-500': workshop.id === activeWorkshopId }"
                    >
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                {{ workshop.name }}
                                <span v-if="workshop.id === activeWorkshopId" class="ml-2 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    Faol
                                </span>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ workshop.owner_name }} · {{ workshop.clients_count }} mijoz · {{ workshop.products_count }} mahsulot
                            </p>
                        </div>
                        <button
                            @click="switchTo(workshop)"
                            class="shrink-0 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                        >
                            {{ workshop.id === activeWorkshopId ? 'Qayta kirish' : 'Kirish' }}
                        </button>
                    </div>
                </div>
                <div v-else class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                    <p class="text-gray-500 dark:text-gray-400">Hali workshoplar yo'q.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
