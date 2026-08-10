<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    supplier: Object,
    balances: Object,
    transactions: Object,
    products: Array,
});

const showPaymentForm = ref(false);

const paymentForm = useForm({
    amount: '',
    currency: 'UZS',
    payment_method: '',
    notes: '',
});

const submitPayment = () => {
    paymentForm.post(route('suppliers.payments.store', props.supplier.id), {
        onSuccess: () => {
            paymentForm.reset();
            showPaymentForm.value = false;
        },
    });
};

const formatMoney = (amount, currency) => {
    if (currency === 'USD') {
        return '$' + new Intl.NumberFormat('en-US').format(amount);
    }
    return new Intl.NumberFormat('uz-UZ').format(amount) + " so'm";
};

const formatDate = (date) => new Date(date).toLocaleDateString('uz-UZ');
</script>

<template>
    <Head :title="`Ta'minotchi: ${supplier.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ supplier.name }}
                </h2>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('suppliers.edit', supplier.id)"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        O'zgartirish
                    </Link>
                    <Link
                        :href="route('suppliers.index')"
                        class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200"
                    >
                        Orqaga
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-5xl space-y-6 px-3 sm:px-6 lg:px-8">
                <!-- Info + Balance -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 md:col-span-2">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Ma'lumotlar</h3>
                            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Telefon</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-300">{{ supplier.phone || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Mas'ul shaxs</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-300">{{ supplier.contact_person || '-' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Manzil</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-300">{{ supplier.address || '-' }}</dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Izoh</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-300">{{ supplier.notes || '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Qarz</h3>
                            <div class="space-y-2">
                                <div v-for="(amount, currency) in balances" :key="currency">
                                    <p
                                        class="text-2xl font-bold"
                                        :class="amount > 0.01 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                                    >
                                        {{ formatMoney(amount, currency) }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="showPaymentForm = !showPaymentForm"
                                class="mt-4 w-full rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                            >
                                To'lov qilish
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div v-if="showPaymentForm" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submitPayment" class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Ta'minotchiga to'lov</h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <InputLabel for="amount" value="Summa *" />
                                <TextInput
                                    id="amount"
                                    v-model="paymentForm.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />
                                <InputError :message="paymentForm.errors.amount" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="currency" value="Valyuta *" />
                                <select
                                    id="currency"
                                    v-model="paymentForm.currency"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="UZS">UZS</option>
                                    <option value="USD">USD</option>
                                </select>
                                <InputError :message="paymentForm.errors.currency" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="payment_method" value="To'lov usuli" />
                                <select
                                    id="payment_method"
                                    v-model="paymentForm.payment_method"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Tanlang</option>
                                    <option value="naqd">Naqd</option>
                                    <option value="karta">Karta</option>
                                    <option value="bank">Bank o'tkazmasi</option>
                                    <option value="click">Click</option>
                                </select>
                                <InputError :message="paymentForm.errors.payment_method" class="mt-2" />
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel for="notes" value="Izoh" />
                                <TextInput
                                    id="notes"
                                    v-model="paymentForm.notes"
                                    type="text"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="paymentForm.errors.notes" class="mt-2" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-4">
                            <PrimaryButton :disabled="paymentForm.processing">
                                Saqlash
                            </PrimaryButton>
                            <button
                                type="button"
                                @click="showPaymentForm = false"
                                class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                            >
                                Bekor qilish
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Transactions -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Tranzaksiyalar tarixi</h3>
                        <div v-if="transactions.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Sana</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Turi</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Summa</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Izoh</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="tx in transactions.data" :key="tx.id">
                                        <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(tx.transaction_date) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2">
                                            <span
                                                v-if="tx.type === 'purchase'"
                                                class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800 dark:bg-red-800 dark:text-red-100"
                                            >
                                                Qarzga olindi
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-800 dark:text-green-100"
                                            >
                                                To'lov
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                                            {{ formatMoney(tx.amount, tx.currency) }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            {{ tx.description || '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div v-if="transactions.links.length > 3" class="mt-4 flex justify-center">
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                    <Link
                                        v-for="(link, index) in transactions.links"
                                        :key="index"
                                        :href="link.url"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold',
                                            link.active
                                                ? 'z-10 bg-indigo-600 text-white'
                                                : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700',
                                            !link.url && 'pointer-events-none opacity-50'
                                        ]"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400">Hali tranzaksiya yo'q</p>
                    </div>
                </div>

                <!-- Linked Products -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Bog'langan mahsulotlar</h3>
                        <div v-if="products.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nomi</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kategoriya</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Qoldiq</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    <tr v-for="product in products" :key="product.id">
                                        <td class="px-4 py-2">
                                            <Link :href="route('products.show', product.id)" class="text-sm font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
                                                {{ product.name }}
                                            </Link>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            {{ product.category?.name || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-900 dark:text-gray-300">
                                            {{ product.stock_quantity }} {{ product.unit }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400">Bu ta'minotchiga bog'langan mahsulot yo'q</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
