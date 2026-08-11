<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    filters: Object,
    report: Object,
    canSeeBranchBreakdown: Boolean,
});

const range = reactive({
    date_from: props.filters.date_from,
    date_to: props.filters.date_to,
});

const applyRange = () => {
    router.get(route('reports.reminders'), { ...range }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const formatMoney = (amount) => new Intl.NumberFormat('uz-UZ').format(Math.round(amount || 0)) + " so'm";
</script>

<template>
    <Head title="Eslatma effektivligi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Eslatma effektivligi
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

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Faqat muddati allaqachon kelgan servislar hisoblanadi (kelajakdagi eslatmalar bo'yicha hali "qaytdi/qaytmadi" ma'lum emas).
                    <br />
                    "Ochilganlik" ko'rsatkichi hozircha kuzatilmaydi — bu Telegram Mini App ishga tushgach qo'shiladi.
                </p>

                <!-- Yuborilgan -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-5">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tanlangan davrda yuborilgan eslatmalar</p>
                        <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ report.sent_total }}</p>
                    </div>
                </div>

                <!-- Taqqoslash -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="overflow-hidden rounded-lg border-2 border-green-400 bg-white shadow-sm dark:bg-gray-800">
                        <div class="bg-green-50 px-5 py-3 dark:bg-green-900/30">
                            <h3 class="font-semibold text-green-800 dark:text-green-200">🔔 Eslatma yuborilgan</h3>
                        </div>
                        <div class="grid grid-cols-3 gap-2 p-5 text-center">
                            <div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ report.with_reminder.total }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">servis</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ report.with_reminder.return_rate }}%</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">30 kun ichida qaytdi</div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ formatMoney(report.with_reminder.revenue) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">qaytganlar tushumi</div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border-2 border-gray-300 bg-white shadow-sm dark:bg-gray-800">
                        <div class="bg-gray-50 px-5 py-3 dark:bg-gray-900/50">
                            <h3 class="font-semibold text-gray-700 dark:text-gray-300">🔕 Eslatmasiz (masalan, Telegram ulanmagan)</h3>
                        </div>
                        <div class="grid grid-cols-3 gap-2 p-5 text-center">
                            <div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ report.without_reminder.total }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">servis</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ report.without_reminder.return_rate }}%</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">30 kun ichida qaytdi</div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ formatMoney(report.without_reminder.revenue) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">qaytganlar tushumi</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filial kesimi (Pro/Maxsus) -->
                <div v-if="canSeeBranchBreakdown && report.by_branch && Object.keys(report.by_branch).length" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h3 class="font-medium text-gray-900 dark:text-white">Filiallar kesimida</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                                    <th class="px-5 py-2">Filial</th>
                                    <th class="px-5 py-2">Eslatma bilan qaytish %</th>
                                    <th class="px-5 py-2">Eslatmasiz qaytish %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="(row, branchName) in report.by_branch" :key="branchName">
                                    <td class="px-5 py-2 font-medium text-gray-900 dark:text-white">{{ branchName }}</td>
                                    <td class="px-5 py-2 text-green-600 dark:text-green-400">{{ row.with_reminder.return_rate }}% ({{ row.with_reminder.total }} ta)</td>
                                    <td class="px-5 py-2 text-gray-500 dark:text-gray-400">{{ row.without_reminder.return_rate }}% ({{ row.without_reminder.total }} ta)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else-if="!canSeeBranchBreakdown" class="rounded-lg border border-dashed border-gray-300 p-5 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    Filiallar kesimidagi tahlil — Pro va Maxsus tariflarda mavjud.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
