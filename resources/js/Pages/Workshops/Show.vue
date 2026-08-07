<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    workshop: Object,
});

const planLabels = {
    free: 'Bepul',
    start: 'Start',
    pro: 'Pro',
    business: 'Biznes',
};

const formatDate = (value) => {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('uz-UZ');
};

const switchTo = () => {
    router.post(route('workshops.switch', props.workshop.id));
};

const stats = [
    { label: 'Mijozlar', value: props.workshop.clients_count },
    { label: 'Mahsulotlar', value: props.workshop.products_count },
    { label: 'Kategoriyalar', value: props.workshop.categories_count },
    { label: 'Xarajatlar', value: props.workshop.expenses_count },
    { label: 'Filiallar', value: props.workshop.branches_count },
    { label: 'Inventarizatsiyalar', value: props.workshop.inventories_count },
];
</script>

<template>
    <Head :title="workshop.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {{ workshop.name }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ workshop.owner_name || workshop.user?.name }} · {{ workshop.phone }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="switchTo"
                        class="rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500"
                    >
                        Nomidan kirish
                    </button>
                    <Link
                        :href="route('workshops.edit', workshop.id)"
                        class="rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500"
                    >
                        O'zgartirish
                    </Link>
                    <Link
                        :href="route('workshops.index')"
                        class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        ← Orqaga
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-5xl px-3 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div v-for="stat in stats" :key="stat.label" class="rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ stat.label }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <!-- Kompaniya ma'lumotlari -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">Kompaniya ma'lumotlari</h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Telefon</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.phone }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.email || '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Manzil</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.address || '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Tarif</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ planLabels[workshop.subscription_plan] || workshop.subscription_plan }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Obuna tugash sanasi</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ formatDate(workshop.subscription_expires_at) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Holati</dt>
                                    <dd>
                                        <span v-if="workshop.is_active" class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            Faol
                                        </span>
                                        <span v-else class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Nofaol
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Direktor va filiallar -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">Direktor</h3>
                            <dl class="mb-6 space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Ismi</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.user?.name || '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Telefon (login)</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.user?.phone || '-' }}</dd>
                                </div>
                            </dl>

                            <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">Filiallar</h3>
                            <ul v-if="workshop.branches?.length" class="space-y-1 text-sm">
                                <li v-for="branch in workshop.branches" :key="branch.id" class="flex items-center justify-between">
                                    <span class="text-gray-900 dark:text-white">{{ branch.name }} ({{ branch.code }})</span>
                                    <span
                                        :class="branch.is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400'"
                                        class="text-xs"
                                    >
                                        {{ branch.is_active ? 'Faol' : 'Nofaol' }}
                                    </span>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-gray-500 dark:text-gray-400">Filiallar mavjud emas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
