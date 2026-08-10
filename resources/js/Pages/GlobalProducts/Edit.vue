<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    product: Object,
    categoryNames: Array,
});

const form = useForm({
    category_name: props.product.global_category?.name || '',
    name: props.product.name,
    sku: props.product.sku || '',
    description: props.product.description || '',
    unit: props.product.unit,
    barcode: props.product.barcode || '',
    is_active: props.product.is_active,
});

const submit = () => {
    form.put(route('global-products.update', props.product.id));
};
</script>

<template>
    <Head title="Katalog mahsulotini tahrirlash" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Katalog mahsulotini tahrirlash
            </h2>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-3xl px-3 sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="space-y-6">
                            <!-- Name -->
                            <div>
                                <InputLabel for="name" value="Mahsulot nomi *" />
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
                                <!-- Category -->
                                <div>
                                    <InputLabel for="category_name" value="Kategoriya" />
                                    <TextInput
                                        id="category_name"
                                        v-model="form.category_name"
                                        type="text"
                                        list="category-names"
                                        placeholder="Masalan: Motor moylari"
                                        class="mt-1 block w-full"
                                    />
                                    <datalist id="category-names">
                                        <option v-for="name in categoryNames" :key="name" :value="name" />
                                    </datalist>
                                    <InputError :message="form.errors.category_name" class="mt-2" />
                                </div>

                                <!-- SKU -->
                                <div>
                                    <InputLabel for="sku" value="SKU/Artikul" />
                                    <TextInput
                                        id="sku"
                                        v-model="form.sku"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.sku" class="mt-2" />
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <InputLabel for="description" value="Tavsifi" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                                <InputError :message="form.errors.description" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Unit -->
                                <div>
                                    <InputLabel for="unit" value="O'lchov birligi *" />
                                    <select
                                        id="unit"
                                        v-model="form.unit"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="dona">Dona</option>
                                        <option value="litr">Litr</option>
                                        <option value="kg">Kg</option>
                                        <option value="metr">Metr</option>
                                    </select>
                                    <InputError :message="form.errors.unit" class="mt-2" />
                                </div>

                                <!-- Barcode -->
                                <div>
                                    <InputLabel for="barcode" value="Barcode" />
                                    <TextInput
                                        id="barcode"
                                        v-model="form.barcode"
                                        type="text"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.barcode" class="mt-2" />
                                </div>
                            </div>

                            <!-- Is Active -->
                            <div class="flex items-center">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                />
                                <label for="is_active" class="ml-2 text-sm text-gray-900 dark:text-gray-300">
                                    Faol (tadbirkorlarga ko'rinadi)
                                </label>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">
                                    Saqlash
                                </PrimaryButton>
                                <Link
                                    :href="route('global-products.index')"
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
