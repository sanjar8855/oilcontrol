<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: [Number, String, null],
    brands: Array,
});

const emit = defineEmits(['update:modelValue']);

const localBrands = ref([...props.brands]);
const showAdd = ref(false);
const newBrandName = ref('');
const adding = ref(false);
const error = ref('');

const onSelect = (event) => {
    const value = event.target.value;
    emit('update:modelValue', value ? Number(value) : null);
};

const addBrand = async () => {
    const name = newBrandName.value.trim();
    if (!name) {
        return;
    }

    adding.value = true;
    error.value = '';

    try {
        const { data } = await axios.post(route('brands.store'), { name });

        if (!localBrands.value.some((brand) => brand.id === data.id)) {
            localBrands.value.push(data);
            localBrands.value.sort((a, b) => a.name.localeCompare(b.name));
        }

        emit('update:modelValue', data.id);
        newBrandName.value = '';
        showAdd.value = false;
    } catch (e) {
        error.value = "Brend qo'shishda xatolik yuz berdi";
    } finally {
        adding.value = false;
    }
};
</script>

<template>
    <div>
        <select
            :value="modelValue ?? ''"
            @change="onSelect"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
        >
            <option value="">Tanlanmagan</option>
            <option v-for="brand in localBrands" :key="brand.id" :value="brand.id">
                {{ brand.name }}
            </option>
        </select>

        <button
            v-if="!showAdd"
            type="button"
            @click="showAdd = true"
            class="mt-1 text-xs font-semibold text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
        >
            + Yangi brend qo'shish
        </button>

        <div v-else class="mt-2 flex items-center gap-2">
            <input
                v-model="newBrandName"
                type="text"
                placeholder="Brend nomi"
                autofocus
                @keydown.enter.prevent="addBrand"
                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
            />
            <button
                type="button"
                @click="addBrand"
                :disabled="adding"
                class="shrink-0 rounded-md bg-gray-200 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-300 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            >
                Qo'shish
            </button>
            <button
                type="button"
                @click="showAdd = false"
                class="shrink-0 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400"
            >
                Bekor
            </button>
        </div>
        <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
</template>
