<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    product: Object,
});

const form = useForm({
    type: 'in',
    quantity: 1,
    unit_price: props.product.purchase_price,
    reason: '',
    notes: '',
});

const newStock = computed(() => {
    const current = props.product.stock_quantity;
    const qty = parseInt(form.quantity) || 0;
    
    if (form.type === 'in') {
        return current + qty;
    } else if (form.type === 'out') {
        return current - qty;
    } else { // adjustment
        return qty;
    }
});

const submit = () => {
    form.post(route('products.process-stock-adjustment', props.product.id));
};
</script>

<template>
    <Head title="Qoldiq O'zgartirish" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Qoldiq O'zgartirish: {{ product.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <!-- Current Stock Info -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Joriy Qoldiq</h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ product.stock_quantity }} {{ product.unit }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Minimal qoldiq: {{ product.min_stock_level }} {{ product.unit }}
                                </p>
                            </div>
                            <div v-if="product.category" class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Kategoriya</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ product.category.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="p-6">
                        <div class="space-y-6">
                            <!-- Transaction Type -->
                            <div>
                                <InputLabel for="type" value="Amal turi *" />
                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    required
                                >
                                    <option value="in">Kirim (sotib olish)</option>
                                    <option value="out">Chiqim (sotuv/ishlatish)</option>
                                    <option value="adjustment">Tuzatish (to'g'ridan-to'g'ri o'rnatish)</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    <span v-if="form.type === 'in'">Mahsulot sotib olish yoki ombor kirim</span>
                                    <span v-else-if="form.type === 'out'">Mahsulot sotish yoki servisda ishlatish</span>
                                    <span v-else>Qoldiqni to'g'ridan-to'g'ri yangi qiymatga o'rnatish</span>
                                </p>
                                <InputError :message="form.errors.type" class="mt-2" />
                            </div>

                            <!-- Quantity -->
                            <div>
                                <InputLabel for="quantity" value="Miqdor *" />
                                <TextInput
                                    id="quantity"
                                    v-model="form.quantity"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    <span v-if="form.type === 'in'">Qo'shiladigan miqdor</span>
                                    <span v-else-if="form.type === 'out'">Kamaytiriladi miqdor</span>
                                    <span v-else>Yangi qoldiq miqdori</span>
                                </p>
                                <InputError :message="form.errors.quantity" class="mt-2" />
                            </div>

                            <!-- New Stock Preview -->
                            <div class="rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
                                <div class="flex items-center">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                            Yangi qoldiq:
                                        </p>
                                        <p class="mt-1 text-2xl font-bold text-blue-900 dark:text-blue-100">
                                            {{ newStock }} {{ product.unit }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            {{ product.stock_quantity }} → {{ newStock }}
                                        </p>
                                    </div>
                                </div>
                                <p v-if="newStock < 0" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                    <strong>Ogohlantirish:</strong> Yangi qoldiq manfiy bo'ladi!
                                </p>
                                <p v-else-if="newStock <= product.min_stock_level" class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                                    <strong>Ogohlantirish:</strong> Qoldiq minimal darajadan kam yoki teng!
                                </p>
                            </div>

                            <!-- Unit Price -->
                            <div>
                                <InputLabel for="unit_price" value="Birlik narxi" />
                                <TextInput
                                    id="unit_price"
                                    v-model="form.unit_price"
                                    type="number"
                                    step="0.01"
                                    class="mt-1 block w-full"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Birlik narxi (ixtiyoriy, avtomatik tan narxidan olinadi)
                                </p>
                                <InputError :message="form.errors.unit_price" class="mt-2" />
                            </div>

                            <!-- Reason -->
                            <div>
                                <InputLabel for="reason" value="Sabab" />
                                <TextInput
                                    id="reason"
                                    v-model="form.reason"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Masalan: Sotib olish, Servisda ishlatildi"
                                />
                                <InputError :message="form.errors.reason" class="mt-2" />
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

                            <!-- Actions -->
                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing || newStock < 0">
                                    Saqlash
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
