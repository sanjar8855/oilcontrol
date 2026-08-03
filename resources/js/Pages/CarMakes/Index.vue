<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';

const props = defineProps({
    carMakes: Array,
    products: Array,
});

const productOptions = props.products.map((p) => ({
    value: p.id,
    label: `${p.name} (${p.unit})`,
}));

// Yangi marka qo'shish
const makeForm = useForm({ name: '' });
const submitNewMake = () => {
    makeForm.post(route('car-makes.store'), {
        preserveScroll: true,
        onSuccess: () => makeForm.reset(),
    });
};

// Marka nomini tahrirlash
const editingMakeId = ref(null);
const editMakeForm = useForm({ name: '' });
const startEditMake = (make) => {
    editingMakeId.value = make.id;
    editMakeForm.name = make.name;
};
const cancelEditMake = () => {
    editingMakeId.value = null;
};
const submitEditMake = (make) => {
    editMakeForm.put(route('car-makes.update', make.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingMakeId.value = null;
        },
    });
};
const deleteMake = (make) => {
    if (confirm(`Haqiqatan ham "${make.name}" markasini va uning barcha turlarini o'chirmoqchimisiz?`)) {
        router.delete(route('car-makes.destroy', make.id), { preserveScroll: true });
    }
};

// Yangi tur (model) qo'shish
const newModelForms = ref({});
const getNewModelForm = (makeId) => {
    if (!newModelForms.value[makeId]) {
        newModelForms.value[makeId] = useForm({
            car_make_id: makeId,
            name: '',
            oil_capacity_liters: '',
            antifreeze_capacity_min_liters: '',
            antifreeze_capacity_max_liters: '',
        });
    }
    return newModelForms.value[makeId];
};
const submitNewModel = (makeId) => {
    const form = getNewModelForm(makeId);
    form.post(route('car-makes.models.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.car_make_id = makeId;
        },
    });
};

// Tur (model) ma'lumotlarini tahrirlash
const editingModelId = ref(null);
const editModelForm = useForm({
    name: '',
    oil_capacity_liters: '',
    antifreeze_capacity_min_liters: '',
    antifreeze_capacity_max_liters: '',
});
const startEditModel = (model) => {
    editingModelId.value = model.id;
    editModelForm.name = model.name;
    editModelForm.oil_capacity_liters = model.oil_capacity_liters ?? '';
    editModelForm.antifreeze_capacity_min_liters = model.antifreeze_capacity_min_liters ?? '';
    editModelForm.antifreeze_capacity_max_liters = model.antifreeze_capacity_max_liters ?? '';
};
const cancelEditModel = () => {
    editingModelId.value = null;
};
const submitEditModel = (model) => {
    editModelForm.put(route('car-makes.models.update', model.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingModelId.value = null;
        },
    });
};
const deleteModel = (model) => {
    if (confirm(`Haqiqatan ham "${model.name}" turini o'chirmoqchimisiz?`)) {
        router.delete(route('car-makes.models.destroy', model.id), { preserveScroll: true });
    }
};

// Mahsulot bog'lash
const linkProductForms = ref({});
const getLinkProductForm = (modelId) => {
    if (!linkProductForms.value[modelId]) {
        linkProductForms.value[modelId] = useForm({ product_id: null, quantity: 1 });
    }
    return linkProductForms.value[modelId];
};
const submitLinkProduct = (modelId) => {
    const form = getLinkProductForm(modelId);
    if (!form.product_id) {
        return;
    }
    form.post(route('car-makes.models.products.attach', modelId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
const unlinkProduct = (modelId, product) => {
    if (confirm(`"${product.name}" mahsulotini shu turdan uzmoqchimisiz?`)) {
        router.delete(route('car-makes.models.products.detach', [modelId, product.id]), { preserveScroll: true });
    }
};
const fillOilQuantity = (modelId, oilCapacity) => {
    getLinkProductForm(modelId).quantity = oilCapacity;
};

// Bog'langan mahsulot miqdorini tahrirlash
const editingProductQty = ref(null);
const editProductQtyForm = useForm({ quantity: 1 });
const startEditProductQty = (modelId, product) => {
    editingProductQty.value = `${modelId}-${product.id}`;
    editProductQtyForm.quantity = product.pivot.quantity;
};
const cancelEditProductQty = () => {
    editingProductQty.value = null;
};
const submitEditProductQty = (modelId, product) => {
    editProductQtyForm.put(route('car-makes.models.products.update', [modelId, product.id]), {
        preserveScroll: true,
        onSuccess: () => {
            editingProductQty.value = null;
        },
    });
};
</script>

<template>
    <Head title="Avtomobil markalari va turlari" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Avtomobil markalari va turlari
            </h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="mx-auto max-w-4xl px-3 sm:px-6 lg:px-8">
                <!-- Yangi marka qo'shish -->
                <div class="mb-6 overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="p-4 sm:p-6">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                            Yangi marka qo'shish
                        </h3>
                        <form @submit.prevent="submitNewMake" class="flex gap-2">
                            <input
                                v-model="makeForm.name"
                                type="text"
                                required
                                placeholder="Masalan: Chevrolet"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <button
                                type="submit"
                                :disabled="makeForm.processing"
                                class="shrink-0 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                            >
                                Qo'shish
                            </button>
                        </form>
                        <div v-if="makeForm.errors.name" class="mt-1 text-sm text-red-600">
                            {{ makeForm.errors.name }}
                        </div>
                    </div>
                </div>

                <!-- Markalar ro'yxati -->
                <div class="space-y-4">
                    <div
                        v-for="make in carMakes"
                        :key="make.id"
                        class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                            <template v-if="editingMakeId === make.id">
                                <form @submit.prevent="submitEditMake(make)" class="flex flex-1 gap-2">
                                    <input
                                        v-model="editMakeForm.name"
                                        type="text"
                                        required
                                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="editMakeForm.processing"
                                        class="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                                    >
                                        Saqlash
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelEditMake"
                                        class="shrink-0 rounded-md bg-gray-200 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        Bekor qilish
                                    </button>
                                </form>
                            </template>
                            <template v-else>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ make.name }}
                                    <span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">
                                        ({{ make.car_models.length }} ta tur)
                                    </span>
                                </h3>
                                <div class="flex gap-3 text-sm font-medium">
                                    <button
                                        @click="startEditMake(make)"
                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        Tahrirlash
                                    </button>
                                    <button
                                        @click="deleteMake(make)"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        O'chirish
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="model in make.car_models" :key="model.id" class="p-4">
                                <!-- Tahrirlash rejimi -->
                                <template v-if="editingModelId === model.id">
                                    <form @submit.prevent="submitEditModel(model)" class="space-y-2">
                                        <input
                                            v-model="editModelForm.name"
                                            type="text"
                                            required
                                            placeholder="Tur nomi"
                                            class="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                        <div class="grid grid-cols-3 gap-2">
                                            <div>
                                                <label class="block text-xs text-gray-500 dark:text-gray-400">Moy (litr)</label>
                                                <input
                                                    v-model="editModelForm.oil_capacity_liters"
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    placeholder="3.8"
                                                    class="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 dark:text-gray-400">Antifriz min (litr)</label>
                                                <input
                                                    v-model="editModelForm.antifreeze_capacity_min_liters"
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    placeholder="5"
                                                    class="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 dark:text-gray-400">Antifriz max (litr)</label>
                                                <input
                                                    v-model="editModelForm.antifreeze_capacity_max_liters"
                                                    type="number"
                                                    step="0.1"
                                                    min="0"
                                                    placeholder="7"
                                                    class="block w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button
                                                type="submit"
                                                :disabled="editModelForm.processing"
                                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                                            >
                                                Saqlash
                                            </button>
                                            <button
                                                type="button"
                                                @click="cancelEditModel"
                                                class="rounded-md bg-gray-200 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                                            >
                                                Bekor qilish
                                            </button>
                                        </div>
                                    </form>
                                </template>

                                <!-- Ko'rish rejimi -->
                                <template v-else>
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ model.name }}</span>
                                            <span
                                                v-if="model.oil_capacity_liters"
                                                class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-900 dark:text-amber-200"
                                            >
                                                🛢 {{ model.oil_capacity_liters }} L
                                            </span>
                                            <span
                                                v-if="model.antifreeze_capacity_min_liters || model.antifreeze_capacity_max_liters"
                                                class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-xs text-sky-800 dark:bg-sky-900 dark:text-sky-200"
                                            >
                                                ❄️ {{ model.antifreeze_capacity_min_liters }}-{{ model.antifreeze_capacity_max_liters }} L
                                            </span>
                                        </div>
                                        <div class="flex gap-3 text-sm font-medium">
                                            <button
                                                @click="startEditModel(model)"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                Tahrirlash
                                            </button>
                                            <button
                                                @click="deleteModel(model)"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                O'chirish
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- Bog'langan mahsulotlar (workshop mavjud bo'lsa) -->
                                <div v-if="products.length > 0" class="mt-2 pl-1">
                                    <div v-if="model.products.length > 0" class="mb-2 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="product in model.products"
                                            :key="product.id"
                                            class="inline-flex items-center gap-1 rounded-full bg-gray-100 py-0.5 pl-2 pr-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                        >
                                            <template v-if="editingProductQty === `${model.id}-${product.id}`">
                                                <form @submit.prevent="submitEditProductQty(model.id, product)" class="flex items-center gap-1">
                                                    {{ product.name }} ×
                                                    <input
                                                        v-model="editProductQtyForm.quantity"
                                                        type="number"
                                                        step="0.1"
                                                        min="0.01"
                                                        autofocus
                                                        class="w-14 rounded border-gray-300 py-0 text-xs dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                                    />
                                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">✓</button>
                                                    <button type="button" @click="cancelEditProductQty" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">✕</button>
                                                </form>
                                            </template>
                                            <template v-else>
                                                {{ product.name }} ×
                                                <button
                                                    @click="startEditProductQty(model.id, product)"
                                                    title="Miqdorni tahrirlash"
                                                    class="font-semibold underline decoration-dotted hover:text-indigo-600 dark:hover:text-indigo-400"
                                                >
                                                    {{ product.pivot.quantity }}
                                                </button>
                                                <button
                                                    @click="unlinkProduct(model.id, product)"
                                                    class="rounded-full p-0.5 text-gray-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900 dark:hover:text-red-400"
                                                >
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </template>
                                        </span>
                                    </div>
                                    <form @submit.prevent="submitLinkProduct(model.id)" class="flex items-center gap-1.5">
                                        <div class="w-56">
                                            <Multiselect
                                                v-model="getLinkProductForm(model.id).product_id"
                                                :options="productOptions"
                                                :searchable="true"
                                                placeholder="Mahsulot bog'lash..."
                                                noOptionsText="Mahsulot topilmadi"
                                                noResultsText="Natija topilmadi"
                                            />
                                        </div>
                                        <input
                                            v-model="getLinkProductForm(model.id).quantity"
                                            type="number"
                                            step="0.1"
                                            min="0.01"
                                            class="w-20 rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                        <button
                                            v-if="model.oil_capacity_liters"
                                            type="button"
                                            @click="fillOilQuantity(model.id, model.oil_capacity_liters)"
                                            title="Moy hajmini miqdor sifatida qo'yish"
                                            class="shrink-0 rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 hover:bg-amber-200 dark:bg-amber-900 dark:text-amber-200 dark:hover:bg-amber-800"
                                        >
                                            🛢 {{ model.oil_capacity_liters }} L
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="getLinkProductForm(model.id).processing"
                                            class="shrink-0 rounded-md bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-300 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                        >
                                            + Bog'lash
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <p v-if="make.car_models.length === 0" class="p-4 text-sm text-gray-500 dark:text-gray-400">
                                Hali turlari yo'q
                            </p>
                        </div>

                        <div class="border-t border-gray-100 p-4 dark:border-gray-700">
                            <form @submit.prevent="submitNewModel(make.id)" class="space-y-2">
                                <div class="flex gap-2">
                                    <input
                                        v-model="getNewModelForm(make.id).name"
                                        type="text"
                                        required
                                        placeholder="Yangi tur (masalan: Cobalt)"
                                        class="flex-1 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <input
                                        v-model="getNewModelForm(make.id).oil_capacity_liters"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        placeholder="Moy L"
                                        title="Motor moyi hajmi (litr)"
                                        class="w-24 shrink-0 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <input
                                        v-model="getNewModelForm(make.id).antifreeze_capacity_min_liters"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        placeholder="Antifriz min"
                                        class="w-28 shrink-0 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <input
                                        v-model="getNewModelForm(make.id).antifreeze_capacity_max_liters"
                                        type="number"
                                        step="0.1"
                                        min="0"
                                        placeholder="Antifriz max"
                                        class="w-28 shrink-0 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="getNewModelForm(make.id).processing"
                                        class="shrink-0 rounded-md bg-gray-200 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-300 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        + Tur qo'shish
                                    </button>
                                </div>
                                <div v-if="getNewModelForm(make.id).errors.name" class="text-sm text-red-600">
                                    {{ getNewModelForm(make.id).errors.name }}
                                </div>
                            </form>
                        </div>
                    </div>

                    <div v-if="carMakes.length === 0" class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                        <p class="text-gray-500 dark:text-gray-400">Hali markalar yo'q. Yuqoridan birinchi markani qo'shing.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
