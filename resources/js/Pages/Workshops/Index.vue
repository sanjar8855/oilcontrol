<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    workshops: Object,
    filters: Object,
    activeWorkshopId: [Number, String, null],
});

const filters = reactive({
    search: props.filters.search || '',
    subscription_plan: props.filters.subscription_plan || '',
    is_active: props.filters.is_active || '',
});

const reload = () => {
    router.get(route('workshops.index'), { ...filters }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

let searchTimeout = null;
watch(() => filters.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(reload, 400);
});
watch(() => [filters.subscription_plan, filters.is_active], reload);

const resetFilters = () => {
    filters.search = '';
    filters.subscription_plan = '';
    filters.is_active = '';
    reload();
};

const planLabels = {
    trial: 'Sinov',
    start: 'Start',
    pro: 'Pro',
    maxsus: 'Maxsus',
};

const switchTo = (workshop) => {
    router.post(route('workshops.switch', workshop.id));
};

const deleteWorkshop = (workshop) => {
    if (confirm(`DIQQAT! "${workshop.name}" kompaniyasini o'chirmoqchimisiz?\n\nBu amal qaytarib bo'lmaydi — kompaniyaga tegishli barcha mijozlar, mahsulotlar, savdolar va boshqa ma'lumotlar butunlay o'chib ketadi!`)) {
        router.delete(route('workshops.destroy', workshop.id));
    }
};
</script>

<template>
    <Head title="Kompaniyalar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Kompaniyalar
                </h2>
                <Link
                    :href="route('workshops.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi kompaniya
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-4 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-4">
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="min-w-[14rem] flex-1">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Qidirish</label>
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    placeholder="Nomi, rahbar yoki telefon bo'yicha..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Tarif</label>
                                <select
                                    v-model="filters.subscription_plan"
                                    class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Barchasi</option>
                                    <option value="trial">Sinov</option>
                                    <option value="start">Start</option>
                                    <option value="pro">Pro</option>
                                    <option value="maxsus">Maxsus</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Holati</label>
                                <select
                                    v-model="filters.is_active"
                                    class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Barchasi</option>
                                    <option value="1">Faol</option>
                                    <option value="0">Nofaol</option>
                                </select>
                            </div>
                            <button
                                type="button"
                                @click="resetFilters"
                                class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                            >
                                Tozalash
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-4">
                        <div v-if="workshops.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kompaniya</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Rahbar / Telefon</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tarif</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Mijoz / Mahsulot</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Holati</th>
                                        <th scope="col" class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Amallar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="workshop in workshops.data" :key="workshop.id">
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ workshop.name }}
                                                <span
                                                    v-if="activeWorkshopId === workshop.id"
                                                    class="ml-1 inline-flex rounded-full bg-indigo-100 px-2 text-xs font-semibold leading-5 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100"
                                                >
                                                    Nomidan ishlanmoqda
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ workshop.branches_count }} filial</div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                            <div>{{ workshop.owner_name || workshop.user?.name || '-' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ workshop.phone }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2">
                                            <span class="inline-flex rounded-full bg-purple-100 px-2 text-xs font-semibold leading-5 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                                                {{ planLabels[workshop.subscription_plan] || workshop.subscription_plan }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                            {{ workshop.clients_count }} / {{ workshop.products_count }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2">
                                            <span v-if="workshop.is_active" class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                Faol
                                            </span>
                                            <span v-else class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Nofaol
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-medium">
                                            <Link :href="route('workshops.show', workshop.id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                Ko'rish
                                            </Link>
                                            <Link :href="route('workshops.edit', workshop.id)" class="ml-3 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                O'zgartirish
                                            </Link>
                                            <button
                                                v-if="activeWorkshopId !== workshop.id"
                                                @click="switchTo(workshop)"
                                                class="ml-3 text-teal-600 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-300"
                                            >
                                                Kirish
                                            </button>
                                            <button @click="deleteWorkshop(workshop)" class="ml-3 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                O'chirish
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Jami {{ workshops.total }} tadan {{ workshops.from }}-{{ workshops.to }} ko'rsatilmoqda
                                </p>
                                <div v-if="workshops.links.length > 3" class="flex justify-center">
                                    <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                        <Link
                                            v-for="(link, index) in workshops.links"
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
                        </div>
                        <div v-else class="py-12 text-center">
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ filters.search || filters.subscription_plan || filters.is_active ? 'Filtrga mos kompaniya topilmadi' : 'Hali kompaniyalar yo\'q' }}
                            </p>
                            <Link
                                :href="route('workshops.create')"
                                class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                            >
                                Birinchi kompaniyani qo'shing
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
