<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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

// Marka yoki moshina nomi bo'yicha qidiruv (1s debounce bilan)
const searchQuery = ref('');
const debouncedSearch = ref('');
let searchTimer = null;
watch(searchQuery, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        debouncedSearch.value = value;
    }, 1000);
});

const filteredMakes = computed(() => {
    const term = debouncedSearch.value.trim().toLowerCase();
    if (!term) {
        return props.carMakes;
    }

    return props.carMakes
        .map((make) => {
            if (make.name.toLowerCase().includes(term)) {
                return make;
            }
            const matchingModels = make.car_models.filter((model) => model.name.toLowerCase().includes(term));
            return matchingModels.length > 0 ? { ...make, car_models: matchingModels } : null;
        })
        .filter(Boolean);
});

// Marka qo'shish/tahrirlash oynasi (modal)
const showMakeModal = ref(false);
const makeModalMode = ref('create'); // 'create' | 'edit'
const editingMake = ref(null);
const makeForm = useForm({ name: '', sort_order: 100 });

const openCreateMakeModal = () => {
    makeModalMode.value = 'create';
    editingMake.value = null;
    makeForm.reset();
    makeForm.clearErrors();
    makeForm.sort_order = 100;
    showMakeModal.value = true;
};
const openEditMakeModal = (make) => {
    makeModalMode.value = 'edit';
    editingMake.value = make;
    makeForm.clearErrors();
    makeForm.name = make.name;
    makeForm.sort_order = make.sort_order;
    showMakeModal.value = true;
};
const closeMakeModal = () => {
    showMakeModal.value = false;
    makeForm.reset();
    makeForm.clearErrors();
};
const submitMakeModal = () => {
    if (makeModalMode.value === 'create') {
        makeForm.post(route('car-makes.store'), {
            preserveScroll: true,
            onSuccess: closeMakeModal,
        });
    } else {
        makeForm.put(route('car-makes.update', editingMake.value.id), {
            preserveScroll: true,
            onSuccess: closeMakeModal,
        });
    }
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
    router.delete(route('car-makes.models.products.detach', [modelId, product.id]), { preserveScroll: true });
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
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Avtomobil markalari va turlari
                </h2>
                <button
                    type="button"
                    @click="openCreateMakeModal"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Yangi moshina markasi qo'shish
                </button>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-4xl px-3 sm:px-6 lg:px-8">
                <!-- Qidiruv -->
                <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="p-4">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Marka yoki moshina turi nomi bo'yicha qidirish..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>

                <!-- Markalar ro'yxati -->
                <div class="space-y-4">
                    <div
                        v-for="make in filteredMakes"
                        :key="make.id"
                        class="rounded-lg bg-white shadow dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                <span class="mr-1 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    #{{ make.sort_order }}
                                </span>
                                {{ make.name }}
                                <span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">
                                    ({{ make.car_models.length }} ta tur)
                                </span>
                            </h3>
                            <div class="flex gap-3 text-sm font-medium">
                                <button
                                    @click="openEditMakeModal(make)"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                >
                                    O'zgartirish
                                </button>
                                <button
                                    @click="deleteMake(make)"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    O'chirish
                                </button>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="model in make.car_models" :key="model.id" class="p-4">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <!-- Chap ustun: tur ma'lumotlari -->
                            <div>
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
                                                    step="any"
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
                                                    step="any"
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
                                                    step="any"
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
                                                O'zgartirish
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
                            </div>

                            <!-- O'ng ustun: bog'langan mahsulotlar (faqat select2 orqali qo'shiladi) -->
                            <div
                                v-if="products.length > 0"
                                class="border-t border-gray-100 pt-3 dark:border-gray-700 lg:border-l lg:border-t-0 lg:pl-4 lg:pt-0"
                            >
                                <h5 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Bog'langan mahsulotlar
                                </h5>

                                <form @submit.prevent="submitLinkProduct(model.id)" class="mb-2 flex flex-wrap items-center gap-1.5">
                                    <div class="w-full min-w-[10rem] flex-1">
                                        <Multiselect
                                            v-model="getLinkProductForm(model.id).product_id"
                                            :options="productOptions"
                                            :searchable="true"
                                            placeholder="Mahsulot qo'shish..."
                                            noOptionsText="Mahsulot topilmadi"
                                            noResultsText="Natija topilmadi"
                                        />
                                    </div>
                                    <input
                                        v-model="getLinkProductForm(model.id).quantity"
                                        type="number"
                                        step="any"
                                        min="0.01"
                                        title="Miqdori"
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
                                        :disabled="getLinkProductForm(model.id).processing || !getLinkProductForm(model.id).product_id"
                                        class="shrink-0 rounded-md bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-300 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        + Qo'shish
                                    </button>
                                </form>

                                <div v-if="model.products.length > 0" class="space-y-1">
                                    <div
                                        v-for="product in model.products"
                                        :key="product.id"
                                        class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        <input
                                            :id="`linked-product-${model.id}-${product.id}`"
                                            type="checkbox"
                                            checked
                                            title="O'chirilsa bog'lanish uziladi"
                                            @change="unlinkProduct(model.id, product)"
                                            class="shrink-0 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800"
                                        />
                                        <label
                                            :for="`linked-product-${model.id}-${product.id}`"
                                            class="min-w-0 flex-1 cursor-pointer truncate text-gray-700 dark:text-gray-300"
                                        >
                                            {{ product.name }}
                                        </label>

                                        <template v-if="editingProductQty === `${model.id}-${product.id}`">
                                            <form @submit.prevent="submitEditProductQty(model.id, product)" class="flex shrink-0 items-center gap-1">
                                                <input
                                                    v-model="editProductQtyForm.quantity"
                                                    type="number"
                                                    step="any"
                                                    min="0.01"
                                                    autofocus
                                                    class="w-14 rounded border-gray-300 py-0 text-xs dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                                />
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">✓</button>
                                                <button type="button" @click="cancelEditProductQty" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">✕</button>
                                            </form>
                                        </template>
                                        <button
                                            v-else
                                            type="button"
                                            @click="startEditProductQty(model.id, product)"
                                            title="Miqdorni tahrirlash"
                                            class="shrink-0 text-xs font-semibold text-gray-500 underline decoration-dotted hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                                        >
                                            ×{{ product.pivot.quantity }}
                                        </button>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-gray-400">Hali mahsulot bog'lanmagan</p>
                            </div>
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
                                        step="any"
                                        min="0"
                                        placeholder="Moy L"
                                        title="Motor moyi hajmi (litr)"
                                        class="w-24 shrink-0 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <input
                                        v-model="getNewModelForm(make.id).antifreeze_capacity_min_liters"
                                        type="number"
                                        step="any"
                                        min="0"
                                        placeholder="Antifriz min"
                                        class="w-28 shrink-0 rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <input
                                        v-model="getNewModelForm(make.id).antifreeze_capacity_max_liters"
                                        type="number"
                                        step="any"
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
                        <p class="text-gray-500 dark:text-gray-400">Hali markalar yo'q. Yuqoridagi tugma orqali birinchi markani qo'shing.</p>
                    </div>
                    <div v-else-if="filteredMakes.length === 0" class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                        <p class="text-gray-500 dark:text-gray-400">"{{ searchQuery }}" bo'yicha hech narsa topilmadi.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Marka qo'shish/tahrirlash oynasi -->
        <Modal :show="showMakeModal" @close="closeMakeModal" max-width="sm">
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    {{ makeModalMode === 'create' ? 'Yangi moshina markasi qo\'shish' : 'Markani o\'zgartirish' }}
                </h2>
                <form @submit.prevent="submitMakeModal" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Marka nomi
                        </label>
                        <input
                            v-model="makeForm.name"
                            type="text"
                            required
                            autofocus
                            placeholder="Masalan: Chevrolet"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <div v-if="makeForm.errors.name" class="mt-1 text-sm text-red-600">
                            {{ makeForm.errors.name }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tartib raqami
                        </label>
                        <input
                            v-model="makeForm.sort_order"
                            type="number"
                            min="0"
                            max="9999"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Kichikroq raqam — ro'yxatda yuqoriroqda chiqadi. Ommabop markalarga kichikroq raqam bering.
                        </p>
                        <div v-if="makeForm.errors.sort_order" class="mt-1 text-sm text-red-600">
                            {{ makeForm.errors.sort_order }}
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeMakeModal"
                            class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="makeForm.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                        >
                            Saqlash
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
