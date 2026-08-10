<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    branches: Array,
    roles: Array,
});

const form = useForm({
    name: '',
    phone: '',
    phone_secondary: '',
    password: '',
    password_confirmation: '',
    role: 'employee',
    branch_id: '',
    salary: '',
    hire_date: new Date().toLocaleDateString('sv-SE'),
    position: '',
    employment_status: 'active',
    address: '',
});

const isBranchRequired = computed(() => !['superadmin', 'director'].includes(form.role));

const submit = () => {
    form.post(route('users.store'));
};
</script>

<template>
    <Head title="Yangi Foydalanuvchi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Yangi Foydalanuvchi
                </h2>
                <Link
                    :href="route('users.index')"
                    class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                >
                    ← Orqaga
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-3xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 gap-6">
                                <!-- Ism -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        To'liq ism <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <!-- Telefon raqam -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Telefon raqam <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        placeholder="+998 90 123 45 67"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.phone }}
                                    </div>
                                </div>

                                <!-- Qo'shimcha telefon -->
                                <div>
                                    <label for="phone_secondary" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Qo'shimcha telefon
                                    </label>
                                    <input
                                        id="phone_secondary"
                                        v-model="form.phone_secondary"
                                        type="tel"
                                        placeholder="+998 90 123 45 67"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.phone_secondary" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.phone_secondary }}
                                    </div>
                                </div>

                                <!-- Parol -->
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Parol <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.password }}
                                    </div>
                                </div>

                                <!-- Parolni tasdiqlash -->
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Parolni tasdiqlang <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <!-- Role -->
                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Vazifasi (Role) <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="role"
                                        v-model="form.role"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option v-for="role in roles" :key="role.value" :value="role.value">
                                            {{ role.label }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.role" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.role }}
                                    </div>
                                </div>

                                <!-- Branch -->
                                <div>
                                    <label for="branch_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Filial <span v-if="isBranchRequired" class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="branch_id"
                                        v-model="form.branch_id"
                                        :required="isBranchRequired"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="">Filial tanlang</option>
                                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                                            {{ branch.name }} ({{ branch.code }})
                                        </option>
                                    </select>
                                    <div v-if="!isBranchRequired" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Direktor va super admin uchun filial shart emas.
                                    </div>
                                    <div v-if="form.errors.branch_id" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.branch_id }}
                                    </div>
                                </div>

                                <!-- Divider -->
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                        Xodim ma'lumotlari
                                    </h3>
                                </div>

                                <!-- Lavozim -->
                                <div>
                                    <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Lavozim
                                    </label>
                                    <input
                                        id="position"
                                        v-model="form.position"
                                        type="text"
                                        placeholder="Mexanik, administrator, direktor..."
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.position" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.position }}
                                    </div>
                                </div>

                                <!-- Oylik maosh -->
                                <div>
                                    <label for="salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Oylik maosh (so'm)
                                    </label>
                                    <input
                                        id="salary"
                                        v-model="form.salary"
                                        type="number"
                                        step="0.01"
                                        placeholder="5000000"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.salary" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.salary }}
                                    </div>
                                </div>

                                <!-- Ishga qabul qilingan sana -->
                                <div>
                                    <label for="hire_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Ishga qabul qilingan sana
                                    </label>
                                    <input
                                        id="hire_date"
                                        v-model="form.hire_date"
                                        type="date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.hire_date" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.hire_date }}
                                    </div>
                                </div>

                                <!-- Ish holati -->
                                <div>
                                    <label for="employment_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Ish holati
                                    </label>
                                    <select
                                        id="employment_status"
                                        v-model="form.employment_status"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="active">Faol</option>
                                        <option value="on_leave">Ta'tilda</option>
                                        <option value="terminated">Ishdan bo'shatilgan</option>
                                    </select>
                                    <div v-if="form.errors.employment_status" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.employment_status }}
                                    </div>
                                </div>

                                <!-- Manzil -->
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Uy manzili
                                    </label>
                                    <textarea
                                        id="address"
                                        v-model="form.address"
                                        rows="2"
                                        placeholder="To'liq manzil..."
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    ></textarea>
                                    <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.address }}
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="mt-6 flex justify-end gap-3">
                                <Link
                                    :href="route('users.index')"
                                    class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    Bekor qilish
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
