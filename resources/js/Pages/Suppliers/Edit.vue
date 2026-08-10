<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    supplier: Object,
});

const form = useForm({
    name: props.supplier.name,
    phone: props.supplier.phone || '',
    contact_person: props.supplier.contact_person || '',
    address: props.supplier.address || '',
    notes: props.supplier.notes || '',
    is_active: props.supplier.is_active,
});

const submit = () => {
    form.put(route('suppliers.update', props.supplier.id));
};
</script>

<template>
    <Head title="Ta'minotchini tahrirlash" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Ta'minotchini tahrirlash: {{ supplier.name }}
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

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Phone -->
                                <div>
                                    <InputLabel for="phone" value="Telefon" />
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.phone" class="mt-2" />
                                </div>

                                <!-- Contact Person -->
                                <div>
                                    <InputLabel for="contact_person" value="Mas'ul shaxs" />
                                    <TextInput
                                        id="contact_person"
                                        v-model="form.contact_person"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.contact_person" class="mt-2" />
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <InputLabel for="address" value="Manzil" />
                                <TextInput
                                    id="address"
                                    v-model="form.address"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.address" class="mt-2" />
                            </div>

                            <!-- Notes -->
                            <div>
                                <InputLabel for="notes" value="Izoh" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                                <InputError :message="form.errors.notes" class="mt-2" />
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
                                    :href="route('suppliers.index')"
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
