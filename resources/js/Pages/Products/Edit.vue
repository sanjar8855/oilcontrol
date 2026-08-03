<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';

const props = defineProps({
    product: Object,
    categories: Array,
    carModelGroups: Array,
});

const form = useForm({
    category_id: props.product.category_id,
    name: props.product.name,
    sku: props.product.sku,
    description: props.product.description,
    unit: props.product.unit,
    purchase_price: props.product.purchase_price,
    selling_price: props.product.selling_price,
    min_stock_level: props.product.min_stock_level,
    barcode: props.product.barcode,
    is_active: props.product.is_active,
    track_inventory: props.product.track_inventory,
    car_models: (props.product.car_models || []).map((carModel) => ({
        car_model_id: carModel.id,
        quantity: carModel.pivot.quantity,
    })),
});

const addCarModelLink = () => {
    form.car_models.push({ car_model_id: null, quantity: 1 });
};
const removeCarModelLink = (index) => {
    form.car_models.splice(index, 1);
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        currency: props.product.currency ?? 'UZS',
        purchase_price_uzs: data.purchase_price,
        selling_price_uzs: data.selling_price,
        car_models: data.car_models.filter((link) => link.car_model_id),
    })).put(route('products.update', props.product.id));
};
</script>

<template>
    <Head title="Mahsulot Tahrirlash" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Mahsulot Tahrirlash
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-3xl px-3 sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="space-y-6">
                            <div class="rounded-md bg-yellow-50 p-4 dark:bg-yellow-900/20">
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    <strong>Eslatma:</strong> Qoldiqni o'zgartirish uchun "Qoldiq" tugmasidan foydalaning.
                                </p>
                            </div>

                            <div>
                                <InputLabel for="name" value="Mahsulot nomi *" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="category_id" value="Kategoriya" />
                                    <select
                                        id="category_id"
                                        v-model="form.category_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option :value="null">Tanlang</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.category_id" class="mt-2" />
                                </div>

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

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
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

                                <div>
                                    <InputLabel for="purchase_price" value="Tan narxi *" />
                                    <TextInput
                                        id="purchase_price"
                                        v-model="form.purchase_price"
                                        type="number"
                                        step="0.01"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.purchase_price" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="selling_price" value="Sotuv narxi *" />
                                    <TextInput
                                        id="selling_price"
                                        v-model="form.selling_price"
                                        type="number"
                                        step="0.01"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.selling_price" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="min_stock_level" value="Minimal qoldiq" />
                                    <TextInput
                                        id="min_stock_level"
                                        v-model="form.min_stock_level"
                                        type="number"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError :message="form.errors.min_stock_level" class="mt-2" />
                                </div>

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

                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input
                                        id="is_active"
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                    />
                                    <label for="is_active" class="ml-2 text-sm text-gray-900 dark:text-gray-300">
                                        Faol
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        id="track_inventory"
                                        v-model="form.track_inventory"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                    />
                                    <label for="track_inventory" class="ml-2 text-sm text-gray-900 dark:text-gray-300">
                                        Omborda kuzatilsin
                                    </label>
                                </div>
                            </div>

                            <!-- Bog'langan avtomobil turlari -->
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <InputLabel value="Qaysi avtomobil turlariga mos (ixtiyoriy)" />
                                    <button
                                        type="button"
                                        @click="addCarModelLink"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                    >
                                        + Qo'shish
                                    </button>
                                </div>
                                <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                    Bog'lansa, savdo ekranida shu avtomobil turi tanlanganda ushbu mahsulot belgilangan miqdorda tezkor tavsiya qilinadi.
                                </p>
                                <div v-if="form.car_models.length > 0" class="space-y-2">
                                    <div
                                        v-for="(link, index) in form.car_models"
                                        :key="index"
                                        class="flex items-center gap-2"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <Multiselect
                                                v-model="link.car_model_id"
                                                :options="carModelGroups"
                                                :groups="true"
                                                :searchable="true"
                                                placeholder="Avtomobil turini tanlang"
                                                noOptionsText="Topilmadi"
                                                noResultsText="Natija topilmadi"
                                            />
                                        </div>
                                        <input
                                            v-model="link.quantity"
                                            type="number"
                                            step="any"
                                            min="0.01"
                                            placeholder="Miqdor"
                                            class="w-24 shrink-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        />
                                        <button
                                            type="button"
                                            @click="removeCarModelLink(index)"
                                            class="shrink-0 text-red-600 hover:text-red-800 dark:text-red-400"
                                        >
                                            O'chirish
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">
                                    Yangilash
                                </PrimaryButton>
                                <Link
                                    :href="route('products.show', product.id)"
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
