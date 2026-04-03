<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    users: Object,
});

const deleteUser = (user) => {
    if (confirm(`Haqiqatan ham ${user.name} foydalanuvchisini o'chirmoqchimisiz?`)) {
        router.delete(route('users.destroy', user.id), {
            preserveScroll: true,
        });
    }
};

const getRoleBadgeClass = (role) => {
    switch (role) {
        case 'superadmin':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        case 'director':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'manager':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'employee':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const getRoleLabel = (role) => {
    const labels = {
        superadmin: 'Super Admin',
        director: 'Direktor',
        manager: 'Menejer',
        employee: 'Xodim',
    };
    return labels[role] || role;
};

const getEmploymentStatusBadge = (status) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'on_leave':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'terminated':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const getEmploymentStatusLabel = (status) => {
    const labels = {
        active: 'Faol',
        on_leave: 'Ta\'tilda',
        terminated: 'Ishdan bo\'shatilgan',
    };
    return labels[status] || status;
};

const formatCurrency = (amount) => {
    if (!amount) return '-';
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};
</script>

<template>
    <Head title="Foydalanuvchilar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Xodimlar boshqaruvi
                </h2>
                <Link
                    :href="route('users.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi xodim
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div v-if="users.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Xodim
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Telefon
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Lavozim
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Oylik maosh
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Role
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Holat
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                            Amallar
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                                        <span class="text-indigo-700 dark:text-indigo-200 font-semibold text-lg">
                                                            {{ user.name.charAt(0).toUpperCase() }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ user.name }}
                                                    </div>
                                                    <div v-if="user.branch" class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ user.branch.name }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ user.phone || '-' }}
                                            </div>
                                            <div v-if="user.phone_secondary" class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ user.phone_secondary }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ user.position || '-' }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ formatCurrency(user.salary) }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                                :class="getRoleBadgeClass(user.role)"
                                            >
                                                {{ getRoleLabel(user.role) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                                :class="getEmploymentStatusBadge(user.employment_status)"
                                            >
                                                {{ getEmploymentStatusLabel(user.employment_status) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-3">
                                            <Link
                                                :href="route('users.show', user.id)"
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                                            >
                                                Ko'rish
                                            </Link>
                                            <Link
                                                :href="route('users.edit', user.id)"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                            >
                                                Tahrirlash
                                            </Link>
                                            <button
                                                @click="deleteUser(user)"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400"
                                            >
                                                O'chirish
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                Hali foydalanuvchilar yo'q
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Birinchi foydalanuvchini qo'shing
                            </p>
                            <div class="mt-6">
                                <Link
                                    :href="route('users.create')"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                                >
                                    + Foydalanuvchi qo'shish
                                </Link>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="users.data.length > 0 && (users.prev_page_url || users.next_page_url)" class="mt-6 flex items-center justify-between">
                            <div class="flex flex-1 justify-between sm:hidden">
                                <Link
                                    v-if="users.prev_page_url"
                                    :href="users.prev_page_url"
                                    class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Oldingi
                                </Link>
                                <Link
                                    v-if="users.next_page_url"
                                    :href="users.next_page_url"
                                    class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Keyingi
                                </Link>
                            </div>
                            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        Ko'rsatilmoqda
                                        <span class="font-medium">{{ users.from }}</span>
                                        dan
                                        <span class="font-medium">{{ users.to }}</span>
                                        gacha, jami
                                        <span class="font-medium">{{ users.total }}</span>
                                        ta
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <Link
                                        v-if="users.prev_page_url"
                                        :href="users.prev_page_url"
                                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        Oldingi
                                    </Link>
                                    <Link
                                        v-if="users.next_page_url"
                                        :href="users.next_page_url"
                                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        Keyingi
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
