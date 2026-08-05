<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';

defineProps({
    carMakeGroups: Array,
});

const form = useForm({
    name: '',
    phone: '',
    notes: '',
    plate_number: '',
    make: '',
    avg_daily_km: '',
});

const submit = () => {
    if (form.plate_number.trim()) {
        form.post(route('clients.store-with-vehicle'));
    } else {
        form.post(route('clients.store'));
    }
};
</script>

<template>
    <Head title="Yangi Mijoz" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Yangi Mijoz Qo'shish
                </h2>
                <Link
                    :href="route('clients.index')"
                    class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                >
                    ← Orqaga
                </Link>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-2xl px-3 sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">

                            <!-- Mijoz ismi -->
                            <div>
                                <InputLabel for="name" value="Mijoz ismi *" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    placeholder="Masalan: Abbos Karimov"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Telefon -->
                            <div>
                                <InputLabel for="phone" value="Telefon raqami *" />
                                <TextInput
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="Masalan: +998 90 123 45 67"
                                />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>

                            <!-- Eslatmalar -->
                            <div>
                                <InputLabel for="notes" value="Eslatmalar (ixtiyoriy)" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                                    placeholder="Qo'shimcha ma'lumotlar..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.notes" />
                            </div>

                            <!-- Moshina bo'limi -->
                            <div class="border-t border-gray-200 pt-5 dark:border-gray-700">
                                <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Moshina ma'lumotlari (ixtiyoriy)
                                </h3>

                                <div class="space-y-5">
                                    <!-- Davlat raqami -->
                                    <div>
                                        <InputLabel for="plate_number" value="Davlat raqami" />
                                        <TextInput
                                            id="plate_number"
                                            v-model="form.plate_number"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="Masalan: 01 A 123 BC"
                                        />
                                        <InputError class="mt-2" :message="form.errors.plate_number" />
                                    </div>

                                    <!-- Moshina turi -->
                                    <div>
                                        <InputLabel for="make" value="Moshina turi (ixtiyoriy)" />
                                        <Multiselect
                                            id="make"
                                            v-model="form.make"
                                            :options="carMakeGroups"
                                            :groups="true"
                                            :searchable="true"
                                            placeholder="Mashina turini tanlang"
                                            noOptionsText="Topilmadi"
                                            noResultsText="Natija topilmadi"
                                            class="mt-1"
                                        />
                                        <InputError class="mt-2" :message="form.errors.make" />
                                    </div>

                                    <!-- Kuniga km -->
                                    <div>
                                        <InputLabel for="avg_daily_km" value="Kuniga taxminiy km (ixtiyoriy)" />
                                        <TextInput
                                            id="avg_daily_km"
                                            v-model="form.avg_daily_km"
                                            type="number"
                                            class="mt-1 block w-full"
                                            :min="0"
                                            placeholder="Masalan: 50"
                                        />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Servis eslatmalarini hisoblashda ishlatiladi
                                        </p>
                                        <InputError class="mt-2" :message="form.errors.avg_daily_km" />
                                    </div>
                                </div>
                            </div>

                            <!-- Tugmalar -->
                            <div class="flex items-center justify-end gap-4">
                                <Link
                                    :href="route('clients.index')"
                                    class="rounded-md px-4 py-2 text-sm font-semibold text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                >
                                    Bekor qilish
                                </Link>
                                <PrimaryButton :disabled="form.processing">
                                    Saqlash
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
