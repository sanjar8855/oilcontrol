<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    code: '',
    address: '',
    phone: '',
    email: '',
    is_active: true,
});

const submit = () => {
    form.post(route('branches.store'));
};
</script>

<template>
    <Head title="Yangi Filial" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Yangi Filial
            </h2>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-3xl px-3 sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="space-y-6">
                            <!-- Name -->
                            <div>
                                <InputLabel for="name" value="Nomi *" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <!-- Code -->
                            <div>
                                <InputLabel for="code" value="Kodi" />
                                <TextInput
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Masalan: F001"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Filial kodi (ixtiyoriy)
                                </p>
                                <InputError :message="form.errors.code" class="mt-2" />
                            </div>

                            <!-- Address -->
                            <div>
                                <InputLabel for="address" value="Manzili" />
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                                <InputError :message="form.errors.address" class="mt-2" />
                            </div>

                            <!-- Phone -->
                            <div>
                                <InputLabel for="phone" value="Telefon" />
                                <TextInput
                                    id="phone"
                                    v-model="form.phone"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="+998 XX XXX XX XX"
                                />
                                <InputError :message="form.errors.phone" class="mt-2" />
                            </div>

                            <!-- Email -->
                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>

                            <!-- Is Active -->
                            <div class="flex items-center">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-gray-700 dark:bg-gray-900"
                                />
                                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                    Faol
                                </label>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">
                                    Saqlash
                                </PrimaryButton>
                                <Link
                                    :href="route('branches.index')"
                                    class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                >
                                    Bekor qilish
                                </Link>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
