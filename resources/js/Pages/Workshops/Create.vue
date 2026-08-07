<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    owner_name: '',
    phone: '',
    email: '',
    address: '',
    subscription_plan: 'free',
    subscription_expires_at: '',
    is_active: true,
    director_name: '',
    director_phone: '',
    director_password: '',
});

const submit = () => {
    form.post(route('workshops.store'));
};
</script>

<template>
    <Head title="Yangi Kompaniya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Yangi Kompaniya
                </h2>
                <Link
                    :href="route('workshops.index')"
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
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Kompaniya nomi <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Masalan: Muhammadjon Aka Moy Antifriz"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rahbar F.I.Sh</label>
                                    <input
                                        v-model="form.owner_name"
                                        type="text"
                                        placeholder="Bo'sh qoldirilsa direktor ismi ishlatiladi"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.owner_name" class="mt-1 text-sm text-red-600">{{ form.errors.owner_name }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Kompaniya telefoni <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        placeholder="+998 90 123 45 67"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Manzil</label>
                                    <textarea
                                        v-model="form.address"
                                        rows="2"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    ></textarea>
                                    <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tarif</label>
                                    <select
                                        v-model="form.subscription_plan"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="free">Bepul</option>
                                        <option value="start">Start</option>
                                        <option value="pro">Pro</option>
                                        <option value="business">Biznes</option>
                                    </select>
                                    <div v-if="form.errors.subscription_plan" class="mt-1 text-sm text-red-600">{{ form.errors.subscription_plan }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Obuna tugash sanasi</label>
                                    <input
                                        v-model="form.subscription_expires_at"
                                        type="date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.subscription_expires_at" class="mt-1 text-sm text-red-600">{{ form.errors.subscription_expires_at }}</div>
                                </div>

                                <div class="flex items-center sm:col-span-2">
                                    <input
                                        id="is_active"
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600"
                                    />
                                    <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Kompaniya faol</label>
                                </div>

                                <div class="border-t border-gray-200 pt-4 sm:col-span-2 dark:border-gray-700">
                                    <h3 class="mb-1 text-lg font-medium text-gray-900 dark:text-gray-100">Direktor akkaunti</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Kompaniya rahbari shu telefon raqam va parol bilan tizimga kiradi.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Direktor ismi <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.director_name"
                                        type="text"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.director_name" class="mt-1 text-sm text-red-600">{{ form.errors.director_name }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Direktor telefoni (login) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.director_phone"
                                        type="tel"
                                        required
                                        placeholder="+998 90 123 45 67"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.director_phone" class="mt-1 text-sm text-red-600">{{ form.errors.director_phone }}</div>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Parol <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.director_password"
                                        type="text"
                                        required
                                        placeholder="Kamida 8 ta belgi"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <div v-if="form.errors.director_password" class="mt-1 text-sm text-red-600">{{ form.errors.director_password }}</div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <Link
                                    :href="route('workshops.index')"
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
