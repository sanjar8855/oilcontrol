<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';

const props = defineProps({
    product: Object,
    categoryNames: Array,
    carMakes: Array,
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

// Mos avtomobil turlari
const linkedCarModels = computed(() => props.product.car_models ?? []);

const carModelOptions = props.carMakes.flatMap((make) =>
    make.car_models.map((model) => ({
        value: model.id,
        label: `${make.name} ${model.name}`,
    }))
);

const attachForm = useForm({ car_model_id: null, quantity: 1 });
const submitAttach = () => {
    if (!attachForm.car_model_id) {
        return;
    }
    attachForm.post(route('global-products.car-models.attach', props.product.id), {
        preserveScroll: true,
        onSuccess: () => attachForm.reset(),
    });
};

const detachCarModel = (model) => {
    router.delete(route('global-products.car-models.destroy', [props.product.id, model.id]), {
        preserveScroll: true,
    });
};

const editingQtyId = ref(null);
const qtyForm = useForm({ quantity: 1 });
const startEditQty = (model) => {
    editingQtyId.value = model.id;
    qtyForm.quantity = model.pivot.quantity;
};
const submitQty = (model) => {
    qtyForm.put(route('global-products.car-models.update', [props.product.id, model.id]), {
        preserveScroll: true,
        onSuccess: () => {
            editingQtyId.value = null;
        },
    });
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

                <div class="mt-6 bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="mb-1 text-base font-semibold text-gray-900 dark:text-white">
                            Mos avtomobil turlari
                        </h3>
                        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                            Ushbu mahsulot qaysi avtomobil turlariga mos kelishini va har biriga
                            kerakli miqdorni belgilang. Bu bog'lanish shu mahsulotni o'ziga
                            nusxa olgan barcha tadbirkorlarga avtomatik ko'rinadi.
                        </p>

                        <form @submit.prevent="submitAttach" class="mb-4 flex flex-wrap items-center gap-2">
                            <div class="w-full min-w-[14rem] flex-1 sm:w-auto">
                                <Multiselect
                                    v-model="attachForm.car_model_id"
                                    :options="carModelOptions"
                                    :searchable="true"
                                    placeholder="Avtomobil turini tanlang..."
                                    noOptionsText="Tur topilmadi"
                                    noResultsText="Natija topilmadi"
                                />
                            </div>
                            <input
                                v-model="attachForm.quantity"
                                type="number"
                                step="any"
                                min="0.01"
                                title="Miqdori"
                                class="w-24 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <button
                                type="submit"
                                :disabled="attachForm.processing || !attachForm.car_model_id"
                                class="shrink-0 rounded-md bg-gray-200 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-300 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            >
                                + Bog'lash
                            </button>
                        </form>

                        <div v-if="linkedCarModels.length === 0" class="text-sm text-gray-400">
                            Hali hech qanday avtomobil turi bog'lanmagan.
                        </div>
                        <div v-else class="space-y-1">
                            <div
                                v-for="model in linkedCarModels"
                                :key="model.id"
                                class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <span class="min-w-0 flex-1 truncate text-gray-700 dark:text-gray-300">
                                    {{ model.name }}
                                </span>

                                <template v-if="editingQtyId === model.id">
                                    <form @submit.prevent="submitQty(model)" class="flex shrink-0 items-center gap-1.5">
                                        <input
                                            v-model="qtyForm.quantity"
                                            type="number"
                                            step="any"
                                            min="0.01"
                                            autofocus
                                            class="w-24 rounded border-gray-300 py-1 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                        />
                                        <button type="submit" class="text-lg text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">✓</button>
                                        <button type="button" @click="editingQtyId = null" class="text-lg text-gray-500 hover:text-gray-700 dark:text-gray-400">✕</button>
                                    </form>
                                </template>
                                <button
                                    v-else
                                    type="button"
                                    @click="startEditQty(model)"
                                    title="Miqdorni tahrirlash"
                                    class="shrink-0 text-sm font-semibold text-gray-500 underline decoration-dotted hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                >
                                    ×{{ model.pivot.quantity }}
                                </button>
                                <button
                                    type="button"
                                    @click="detachCarModel(model)"
                                    title="Bog'lanishni uzish"
                                    class="shrink-0 text-red-600 hover:text-red-900 dark:text-red-400"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
