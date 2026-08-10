<script setup>
import { ref, computed, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const SIDEBAR_STORAGE_KEY = 'oilcontrol-sidebar-collapsed';

const collapsed = ref(false);
const mobileOpen = ref(false);

onMounted(() => {
    collapsed.value = localStorage.getItem(SIDEBAR_STORAGE_KEY) === 'true';
});

const toggleCollapsed = () => {
    collapsed.value = !collapsed.value;
    localStorage.setItem(SIDEBAR_STORAGE_KEY, collapsed.value ? 'true' : 'false');
};

const navItems = computed(() => {
    const permissions = page.props.auth.permissions ?? [];

    return [
        { label: 'Boshqaruv', route: 'dashboard', active: 'dashboard', permission: null },
        { label: 'Mijozlar', route: 'clients.index', active: 'clients.*', permission: 'clients.manage' },
        { label: 'Avtomobillar', route: 'vehicles.index', active: 'vehicles.*', permission: 'vehicles.manage' },
        { label: 'Servis Yozuvlari', route: 'service-logs.index', active: 'service-logs.*', permission: 'service-logs.manage' },
        { label: 'Kategoriyalar', route: 'categories.index', active: 'categories.*', permission: 'categories.manage' },
        { label: 'Ta\'minotchilar', route: 'suppliers.index', active: 'suppliers.*', permission: 'suppliers.manage' },
        { label: 'Mahsulotlar', route: 'products.index', active: 'products.*', permission: 'products.manage' },
        { label: 'Inventarizatsiya', route: 'inventories.index', active: 'inventories.*', permission: 'products.manage' },
        { label: 'Xarajatlar', route: 'expenses.index', active: 'expenses.*', permission: 'expenses.manage' },
        { label: 'Hisobotlar', route: 'reports.index', active: 'reports.*', permission: 'reports.view' },
        { label: 'Xodimlar', route: 'users.index', active: 'users.*', permission: 'users.manage' },
        { label: 'Filiallar', route: 'branches.index', active: 'branches.*', permission: 'branches.manage' },
        { label: 'Avto markalari', route: 'car-makes.index', active: 'car-makes.*', permission: 'car-makes.manage' },
        { label: 'Global katalog', route: 'global-products.index', active: 'global-products.*', permission: 'global-products.manage' },
        { label: 'Kompaniyalar', route: 'workshops.index', active: 'workshops.*', permission: 'workshops.manage' },
    ].filter((item) => !item.permission || permissions.includes(item.permission));
});

const initials = (label) => {
    const words = label.trim().split(/\s+/);
    return words.length >= 2
        ? (words[0][0] + words[1][0]).toUpperCase()
        : label.slice(0, 2).toUpperCase();
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Mobil uchun fon (sidebar ochiq bo'lganda) -->
        <div
            v-if="mobileOpen"
            @click="mobileOpen = false"
            class="fixed inset-0 z-30 bg-black/50 sm:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-40 flex h-screen flex-col border-r border-gray-200 bg-white transition-all duration-200 dark:border-gray-700 dark:bg-gray-800 sm:sticky sm:top-0 sm:translate-x-0',
                collapsed ? 'sm:w-20' : 'sm:w-64',
                mobileOpen ? 'w-64 translate-x-0' : 'w-64 -translate-x-full',
            ]"
        >
            <!-- Logo va yig'ish tugmasi -->
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-100 px-4 dark:border-gray-700">
                <Link :href="route('dashboard')" class="flex items-center overflow-hidden">
                    <ApplicationLogo class="h-8 w-8 shrink-0 fill-current text-gray-800 dark:text-gray-200" />
                    <span v-show="!collapsed" class="ml-2 truncate text-sm font-semibold text-gray-800 dark:text-gray-200">
                        OilControl
                    </span>
                </Link>

                <button
                    @click="mobileOpen ? (mobileOpen = false) : toggleCollapsed()"
                    type="button"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    title="Menyuni kichraytirish/kattalashtirish"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Navigatsiya -->
            <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-2 py-4">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    :title="item.label"
                    :class="[
                        'flex items-center rounded-md px-3 py-2 text-sm font-medium transition duration-150 ease-in-out',
                        route().current(item.active)
                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200',
                    ]"
                >
                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-[10px] font-bold"
                        :class="route().current(item.active)
                            ? 'bg-indigo-200 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-200'
                            : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                    >
                        {{ initials(item.label) }}
                    </span>
                    <span v-show="!collapsed" class="ml-3 truncate">{{ item.label }}</span>
                </Link>
            </nav>

            <!-- Faol workshop (superadmin) -->
            <div v-if="$page.props.auth.user.role === 'superadmin'" class="border-t border-gray-100 px-2 py-3 dark:border-gray-700">
                <Link
                    :href="route('workshops.switch.index')"
                    :title="$page.props.activeWorkshop ? $page.props.activeWorkshop.name : 'Workshop tanlanmagan'"
                    class="flex items-center rounded-md bg-indigo-100 px-3 py-2 text-xs font-medium text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-900 dark:text-indigo-200 dark:hover:bg-indigo-800"
                >
                    <span class="shrink-0">↻</span>
                    <span v-show="!collapsed" class="ml-2 truncate">
                        {{ $page.props.activeWorkshop ? $page.props.activeWorkshop.name : 'Workshop tanlanmagan' }}
                    </span>
                </Link>
            </div>

            <!-- Foydalanuvchi -->
            <div class="border-t border-gray-100 p-2 dark:border-gray-700">
                <Dropdown align="right" width="48" direction="up">
                    <template #trigger>
                        <button
                            type="button"
                            class="flex w-full items-center rounded-md px-2 py-2 text-left hover:bg-gray-100 focus:outline-none dark:hover:bg-gray-700"
                        >
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-300 text-xs font-bold text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                {{ initials($page.props.auth.user.name) }}
                            </span>
                            <span v-show="!collapsed" class="ml-2 min-w-0 flex-1 truncate text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $page.props.auth.user.name }}
                            </span>
                        </button>
                    </template>

                    <template #content>
                        <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">
                            Chiqish
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </aside>

        <!-- Asosiy kontent -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Mobil top bar -->
            <div class="flex h-14 shrink-0 items-center border-b border-gray-200 bg-white px-4 dark:border-gray-700 dark:bg-gray-800 sm:hidden">
                <button
                    @click="mobileOpen = true"
                    type="button"
                    class="rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none dark:hover:bg-gray-700 dark:hover:text-gray-300"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <ApplicationLogo class="ml-3 h-7 w-7 fill-current text-gray-800 dark:text-gray-200" />
            </div>

            <!-- Page Content -->
            <main class="flex-1">
                <div v-if="$slots.header" class="mx-auto max-w-7xl px-3 pt-6 sm:px-6 sm:pt-8 lg:px-8">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
