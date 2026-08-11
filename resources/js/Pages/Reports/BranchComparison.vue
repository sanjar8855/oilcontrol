<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';

const props = defineProps({
    filters: Object,
    branches: Array,
});

const range = reactive({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
});

const applyRange = () => {
    router.get(route('reports.branches'), { ...range }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const formatMoney = (amount) => new Intl.NumberFormat('uz-UZ').format(Math.round(amount || 0)) + " so'm";

const topRevenueBranchId = computed(() => {
    if (!props.branches.length) return null;
    return props.branches.reduce((best, b) => (b.revenue > (best?.revenue ?? -Infinity) ? b : best), null)?.id;
});
</script>

<template>
    <Head title="Filiallarni solishtirish" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Filiallarni solishtirish
                </h2>
                <Link
                    :href="route('reports.index')"
                    class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                >
                    ← Hisobotlar
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-3 sm:px-6 lg:px-8">
                <!-- Sana filtri -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex flex-wrap items-end gap-4 p-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Dan</label>
                            <input
                                v-model="range.date_from"
                                type="date"
                                class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Gacha</label>
                            <input
                                v-model="range.date_to"
                                type="date"
                                class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            />
                        </div>
                        <button
                            type="button"
                            @click="applyRange"
                            class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-500"
                        >
                            Ko'rsatish
                        </button>
                    </div>
                </div>

                <div v-if="!branches.length" class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    Hali filiallar mavjud emas
                </div>

                <div v-else class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                                    <th class="px-5 py-3">Filial</th>
                                    <th class="px-5 py-3">Savdolar soni</th>
                                    <th class="px-5 py-3">Tushum</th>
                                    <th class="px-5 py-3">O'rtacha chek</th>
                                    <th class="px-5 py-3">Xarajatlar</th>
                                    <th class="px-5 py-3">Sof foyda</th>
                                    <th class="px-5 py-3">Nasiya berilgan</th>
                                    <th class="px-5 py-3">Yangi mijozlar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr
                                    v-for="branch in branches"
                                    :key="branch.id"
                                    :class="branch.id === topRevenueBranchId ? 'bg-green-50 dark:bg-green-900/20' : ''"
                                >
                                    <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ branch.name }}
                                        <span v-if="branch.id === topRevenueBranchId" class="ml-1 text-xs text-green-600 dark:text-green-400">🏆</span>
                                    </td>
                                    <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ branch.sales_count }}</td>
                                    <td class="px-5 py-3 font-semibold text-indigo-600 dark:text-indigo-400">{{ formatMoney(branch.revenue) }}</td>
                                    <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ formatMoney(branch.avg_ticket) }}</td>
                                    <td class="px-5 py-3 text-red-600 dark:text-red-400">{{ formatMoney(branch.expenses) }}</td>
                                    <td
                                        class="px-5 py-3 font-semibold"
                                        :class="branch.net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                    >
                                        {{ formatMoney(branch.net_profit) }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ formatMoney(branch.credit_extended) }}</td>
                                    <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ branch.new_clients }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
