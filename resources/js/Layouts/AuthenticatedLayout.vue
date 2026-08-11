<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useTranslation } from '@/i18n';

const page = usePage();
const { t } = useTranslation();

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
    const plan = page.props.subscription?.plan;
    const canCompareBranches = plan === 'pro' || plan === 'maxsus';

    return [
        { labelKey: 'nav.dashboard', route: 'dashboard', active: 'dashboard', permission: null },
        { labelKey: 'nav.clients', route: 'clients.index', active: 'clients.*', permission: 'clients.manage' },
        { labelKey: 'nav.vehicles', route: 'vehicles.index', active: 'vehicles.*', permission: 'vehicles.manage' },
        { labelKey: 'nav.service_logs', route: 'service-logs.index', active: 'service-logs.*', permission: 'service-logs.manage' },
        { labelKey: 'nav.categories', route: 'categories.index', active: 'categories.*', permission: 'categories.manage' },
        { labelKey: 'nav.suppliers', route: 'suppliers.index', active: 'suppliers.*', permission: 'suppliers.manage' },
        { labelKey: 'nav.products', route: 'products.index', active: 'products.*', permission: 'products.manage' },
        { labelKey: 'nav.inventories', route: 'inventories.index', active: 'inventories.*', permission: 'products.manage' },
        { labelKey: 'nav.expenses', route: 'expenses.index', active: 'expenses.*', permission: 'expenses.manage' },
        { labelKey: 'nav.reports', route: 'reports.index', active: 'reports.index', permission: 'reports.view' },
        { labelKey: 'nav.reminder_effectiveness', route: 'reports.reminders', active: 'reports.reminders', permission: 'reports.view' },
        { labelKey: 'nav.branch_comparison', route: 'reports.branches', active: 'reports.branches', permission: 'reports.view', hidden: !canCompareBranches },
        { labelKey: 'nav.users', route: 'users.index', active: 'users.*', permission: 'users.manage' },
        { labelKey: 'nav.branches', route: 'branches.index', active: 'branches.*', permission: 'branches.manage' },
        { labelKey: 'nav.car_makes', route: 'car-makes.index', active: 'car-makes.*', permission: 'car-makes.manage' },
        { labelKey: 'nav.global_products', route: 'global-products.index', active: 'global-products.*', permission: 'global-products.manage' },
        { labelKey: 'nav.workshops', route: 'workshops.index', active: 'workshops.*', permission: 'workshops.manage' },
    ].filter((item) => !item.hidden && (!item.permission || permissions.includes(item.permission)));
});

const flashMessage = ref(null);

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            flashMessage.value = { level: 'success', text: flash.success };
        } else if (flash?.error) {
            flashMessage.value = { level: 'error', text: flash.error };
        } else {
            return;
        }

        setTimeout(() => {
            flashMessage.value = null;
        }, 5000);
    },
    { immediate: true, deep: true }
);

const subscriptionBanner = computed(() => {
    const subscription = page.props.subscription;
    if (!subscription) return null;

    if (!subscription.active) {
        return {
            level: 'error',
            message: subscription.onTrial === false && subscription.daysRemaining !== null
                ? t('subscription.subscription_expired')
                : t('subscription.trial_expired'),
        };
    }

    if (subscription.daysRemaining !== null && subscription.daysRemaining <= 7) {
        return {
            level: 'warning',
            message: subscription.onTrial
                ? t('subscription.trial_ending', { days: subscription.daysRemaining })
                : t('subscription.subscription_ending', { days: subscription.daysRemaining }),
        };
    }

    return null;
});

const initials = (label) => {
    const words = label.trim().split(/\s+/);
    return words.length >= 2
        ? (words[0][0] + words[1][0]).toUpperCase()
        : label.slice(0, 2).toUpperCase();
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 -translate-y-2"
        leave-active-class="transition ease-in duration-150"
        leave-to-class="opacity-0"
    >
        <div
            v-if="flashMessage"
            :class="[
                'fixed right-4 top-4 z-50 max-w-sm rounded-lg px-4 py-3 text-sm font-medium shadow-lg',
                flashMessage.level === 'success'
                    ? 'bg-green-600 text-white'
                    : 'bg-red-600 text-white',
            ]"
        >
            {{ flashMessage.text }}
        </div>
    </Transition>

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
                    :title="$t(item.labelKey)"
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
                        {{ initials($t(item.labelKey)) }}
                    </span>
                    <span v-show="!collapsed" class="ml-3 truncate">{{ $t(item.labelKey) }}</span>
                </Link>
            </nav>

            <!-- Faol workshop (superadmin) -->
            <div v-if="$page.props.auth.user.role === 'superadmin'" class="border-t border-gray-100 px-2 py-3 dark:border-gray-700">
                <Link
                    :href="route('workshops.switch.index')"
                    :title="$page.props.activeWorkshop ? $page.props.activeWorkshop.name : $t('nav.workshop_not_selected')"
                    class="flex items-center rounded-md bg-indigo-100 px-3 py-2 text-xs font-medium text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-900 dark:text-indigo-200 dark:hover:bg-indigo-800"
                >
                    <span class="shrink-0">↻</span>
                    <span v-show="!collapsed" class="ml-2 truncate">
                        {{ $page.props.activeWorkshop ? $page.props.activeWorkshop.name : $t('nav.workshop_not_selected') }}
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
                        <DropdownLink :href="route('profile.edit')">{{ $t('nav.profile') }}</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">
                            {{ $t('nav.logout') }}
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
                <div
                    v-if="subscriptionBanner"
                    :class="[
                        'px-4 py-2 text-center text-sm font-medium',
                        subscriptionBanner.level === 'error'
                            ? 'bg-red-600 text-white'
                            : 'bg-yellow-400 text-yellow-950',
                    ]"
                >
                    {{ subscriptionBanner.message }}
                    <Link
                        v-if="$page.props.auth.user.role === 'director'"
                        :href="route('profile.edit')"
                        class="underline"
                    >
                        {{ $t('subscription.details') }}
                    </Link>
                </div>

                <div v-if="$slots.header" class="mx-auto max-w-7xl px-3 pt-6 sm:px-6 sm:pt-8 lg:px-8">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
