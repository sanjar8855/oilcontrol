<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';

const props = defineProps({
    inventory: Object,
});

const isDraft = computed(() => props.inventory.status === 'draft');

const items = reactive(
    props.inventory.items.map((item) => ({
        id: item.id,
        product_name: item.product?.name,
        category_name: item.product?.category?.name,
        unit: item.product?.unit,
        system_quantity: Number(item.system_quantity),
        counted_quantity: item.counted_quantity !== null ? Number(item.counted_quantity) : null,
        notes: item.notes || '',
    }))
);

const difference = (item) => {
    if (item.counted_quantity === null || item.counted_quantity === '') {
        return null;
    }
    return Number(item.counted_quantity) - item.system_quantity;
};

const countedCount = computed(() => items.filter((i) => i.counted_quantity !== null && i.counted_quantity !== '').length);

const saving = reactive({ value: false });
const saveCounts = () => {
    saving.value = true;
    router.put(
        route('inventories.update', props.inventory.id),
        {
            items: items.map((i) => ({
                id: i.id,
                counted_quantity: i.counted_quantity === '' ? null : i.counted_quantity,
                notes: i.notes || null,
            })),
        },
        {
            preserveScroll: true,
            onFinish: () => { saving.value = false; },
        }
    );
};

const completing = reactive({ value: false });
const completeInventory = () => {
    if (!confirm('Inventarizatsiyani yakunlaysizmi? Sanalgan mahsulotlar qoldig\'i tizimda yangilanadi va bu amalni orqaga qaytarib bo\'lmaydi.')) {
        return;
    }
    completing.value = true;
    router.post(route('inventories.complete', props.inventory.id), {}, {
        onFinish: () => { completing.value = false; },
    });
};

const deleteInventory = () => {
    if (confirm('Bu inventarizatsiyani butunlay o\'chirmoqchimisiz?')) {
        router.delete(route('inventories.destroy', props.inventory.id));
    }
};

const formatDateTime = (date) => (date ? new Date(date).toLocaleString('uz-UZ') : '-');
</script>

<template>
    <Head title="Inventarizatsiya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Inventarizatsiya — {{ formatDateTime(inventory.started_at) }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ inventory.branch?.name || 'Barcha filiallar' }} · Boshlagan: {{ inventory.user?.name }}
                    </p>
                </div>
                <Link
                    :href="route('inventories.index')"
                    class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                >
                    ← Orqaga
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <span
                            v-if="inventory.status === 'completed'"
                            class="inline-flex rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800 dark:bg-green-900 dark:text-green-200"
                        >
                            Yakunlangan — {{ formatDateTime(inventory.completed_at) }}
                        </span>
                        <span
                            v-else
                            class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-200"
                        >
                            Jarayonda
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Sanalgan: {{ countedCount }} / {{ items.length }}
                        </span>
                    </div>

                    <div v-if="isDraft" class="flex gap-2">
                        <button
                            @click="deleteInventory"
                            type="button"
                            class="rounded-md bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-300"
                        >
                            O'chirish
                        </button>
                        <button
                            @click="saveCounts"
                            type="button"
                            :disabled="saving.value"
                            class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
                        >
                            {{ saving.value ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </button>
                        <button
                            @click="completeInventory"
                            type="button"
                            :disabled="completing.value"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {{ completing.value ? 'Yakunlanmoqda...' : 'Yakunlash' }}
                        </button>
                    </div>
                </div>

                <div v-if="inventory.notes" class="rounded-lg bg-white p-4 text-sm text-gray-600 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                    <span class="font-medium text-gray-800 dark:text-gray-200">Izoh:</span> {{ inventory.notes }}
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Mahsulot</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kategoriya</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tizimda</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sanalgan</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Farq</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Izoh</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                <tr v-for="item in items" :key="item.id">
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ item.product_name }}
                                        <span class="text-xs font-normal text-gray-400">{{ item.unit }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                        {{ item.category_name || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-sm text-gray-700 dark:text-gray-300">
                                        {{ item.system_quantity }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right">
                                        <input
                                            v-if="isDraft"
                                            v-model.number="item.counted_quantity"
                                            type="number"
                                            step="any"
                                            min="0"
                                            placeholder="—"
                                            class="w-24 rounded-md border-gray-300 py-1 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                        <span v-else class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ item.counted_quantity ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-semibold">
                                        <span v-if="difference(item) === null" class="text-gray-400">—</span>
                                        <span v-else-if="difference(item) === 0" class="text-green-600 dark:text-green-400">0</span>
                                        <span v-else-if="difference(item) > 0" class="text-blue-600 dark:text-blue-400">+{{ difference(item) }}</span>
                                        <span v-else class="text-red-600 dark:text-red-400">{{ difference(item) }}</span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input
                                            v-if="isDraft"
                                            v-model="item.notes"
                                            type="text"
                                            placeholder="Izoh..."
                                            class="w-40 rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                        <span v-else class="text-sm text-gray-500 dark:text-gray-400">{{ item.notes || '-' }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="items.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                        Bu inventarizatsiyada mahsulotlar yo'q
                    </div>
                </div>

                <div v-if="isDraft" class="flex justify-end">
                    <button
                        @click="saveCounts"
                        type="button"
                        :disabled="saving.value"
                        class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
                    >
                        {{ saving.value ? 'Saqlanmoqda...' : 'Saqlash' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
