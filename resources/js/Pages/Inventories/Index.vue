<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    inventories: Object,
});

const formatDateTime = (date) => {
    return new Date(date).toLocaleString('uz-UZ');
};
</script>

<template>
    <Head title="Inventarizatsiya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Inventarizatsiya
                </h2>
                <Link
                    :href="route('inventories.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi inventarizatsiya
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div v-if="inventories.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Boshlangan sana</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Filial</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Boshlagan xodim</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sanalgan / Jami</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Holati</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Amallar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                <tr v-for="inventory in inventories.data" :key="inventory.id">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ formatDateTime(inventory.started_at) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ inventory.branch?.name || 'Barcha filiallar' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ inventory.user?.name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ inventory.counted_items_count }} / {{ inventory.items_count }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            v-if="inventory.status === 'completed'"
                                            class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900 dark:text-green-200"
                                        >
                                            Yakunlangan
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex rounded-full bg-amber-100 px-2 text-xs font-semibold leading-5 text-amber-800 dark:bg-amber-900 dark:text-amber-200"
                                        >
                                            Jarayonda
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <Link
                                            :href="route('inventories.show', inventory.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                        >
                                            Ko'rish
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div v-if="inventories.links.length > 3" class="mt-4 flex justify-center pb-4">
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                <Link
                                    v-for="(link, index) in inventories.links"
                                    :key="index"
                                    :href="link.url"
                                    :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-semibold',
                                        link.active
                                            ? 'z-10 bg-indigo-600 text-white'
                                            : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700',
                                        !link.url && 'pointer-events-none opacity-50'
                                    ]"
                                    v-html="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center">
                        <p class="text-gray-500 dark:text-gray-400">Hali inventarizatsiya o'tkazilmagan</p>
                        <Link
                            :href="route('inventories.create')"
                            class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                        >
                            Birinchi inventarizatsiyani boshlang
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
