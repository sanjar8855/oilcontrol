<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    workshop: Object,
    subscriptionStatus: Object,
    plans: Object,
});

const planLabels = {
    trial: 'Sinov',
    start: 'Start',
    pro: 'Pro',
    maxsus: 'Maxsus',
};

const methodLabels = {
    cash: 'Naqd',
    p2p: 'P2P o\'tkazma',
    click: 'Click',
    payme: 'Payme',
};

const formatDate = (value) => {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('uz-UZ');
};

const formatMoney = (value) => new Intl.NumberFormat('uz-UZ').format(value ?? 0);

const switchTo = () => {
    router.post(route('workshops.switch', props.workshop.id));
};

const paymentForm = useForm({
    plan: props.workshop.subscription_plan === 'trial' ? 'start' : props.workshop.subscription_plan,
    amount: props.plans?.[props.workshop.subscription_plan]?.price || '',
    method: 'cash',
    period_days: 30,
    paid_at: new Date().toISOString().slice(0, 10),
    notes: '',
});

const applyPlanPrice = () => {
    const price = props.plans?.[paymentForm.plan]?.price;
    if (price) {
        paymentForm.amount = price;
    }
};

const submitPayment = () => {
    paymentForm.post(route('subscription-payments.store', props.workshop.id), {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset('notes');
        },
    });
};

const stats = [
    { label: 'Mijozlar', value: props.workshop.clients_count },
    { label: 'Mahsulotlar', value: props.workshop.products_count },
    { label: 'Kategoriyalar', value: props.workshop.categories_count },
    { label: 'Xarajatlar', value: props.workshop.expenses_count },
    { label: 'Filiallar', value: props.workshop.branches_count },
    { label: 'Inventarizatsiyalar', value: props.workshop.inventories_count },
];
</script>

<template>
    <Head :title="workshop.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {{ workshop.name }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ workshop.owner_name || workshop.user?.name }} · {{ workshop.phone }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="switchTo"
                        class="rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500"
                    >
                        Nomidan kirish
                    </button>
                    <Link
                        :href="route('workshops.edit', workshop.id)"
                        class="rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500"
                    >
                        O'zgartirish
                    </Link>
                    <Link
                        :href="route('workshops.index')"
                        class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        ← Orqaga
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-5xl px-3 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div v-for="stat in stats" :key="stat.label" class="rounded-lg bg-white p-4 text-center shadow-sm dark:bg-gray-800">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ stat.label }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <!-- Kompaniya ma'lumotlari -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">Kompaniya ma'lumotlari</h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Telefon</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.phone }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.email || '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Manzil</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.address || '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Tarif</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ planLabels[workshop.subscription_plan] || workshop.subscription_plan }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Obuna tugash sanasi</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ formatDate(workshop.subscription_expires_at) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Holati</dt>
                                    <dd>
                                        <span v-if="workshop.is_active" class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            Faol
                                        </span>
                                        <span v-else class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Nofaol
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Direktor va filiallar -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div class="p-6">
                            <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">Direktor</h3>
                            <dl class="mb-6 space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Ismi</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.user?.name || '-' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Telefon (login)</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ workshop.user?.phone || '-' }}</dd>
                                </div>
                            </dl>

                            <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">Filiallar</h3>
                            <ul v-if="workshop.branches?.length" class="space-y-1 text-sm">
                                <li v-for="branch in workshop.branches" :key="branch.id" class="flex items-center justify-between">
                                    <span class="text-gray-900 dark:text-white">{{ branch.name }} ({{ branch.code }})</span>
                                    <span
                                        :class="branch.is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400'"
                                        class="text-xs"
                                    >
                                        {{ branch.is_active ? 'Faol' : 'Nofaol' }}
                                    </span>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-gray-500 dark:text-gray-400">Filiallar mavjud emas</p>
                        </div>
                    </div>
                </div>

                <!-- Obuna holati -->
                <div class="mt-4 overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Obuna holati</h3>
                            <span
                                v-if="subscriptionStatus.active"
                                :class="subscriptionStatus.onTrial ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' : 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'"
                                class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                            >
                                {{ subscriptionStatus.onTrial ? 'Sinov muddatida' : 'Faol' }}
                            </span>
                            <span v-else class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800 dark:bg-red-800 dark:text-red-100">
                                Tugagan (faqat o'qish rejimi)
                            </span>
                        </div>

                        <div class="mb-6 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">Kunlar qoldi</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ subscriptionStatus.daysRemaining ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">Filiallar</div>
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ workshop.branches_count }} / {{ subscriptionStatus.limits.branches ?? '∞' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">Foydalanuvchilar</div>
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ subscriptionStatus.usersCount }} / {{ subscriptionStatus.limits.users ?? '∞' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-500 dark:text-gray-400">Narx</div>
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ subscriptionStatus.limits.price ? formatMoney(subscriptionStatus.limits.price) + " so'm/oy" : 'Kelishuv' }}
                                </div>
                            </div>
                        </div>

                        <!-- To'lov qo'shish formasi -->
                        <form @submit.prevent="submitPayment" class="mb-6 grid grid-cols-1 gap-3 rounded-lg bg-gray-50 p-4 sm:grid-cols-3 lg:grid-cols-6 dark:bg-gray-900/40">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Tarif</label>
                                <select
                                    v-model="paymentForm.plan"
                                    @change="applyPlanPrice"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                >
                                    <option value="start">Start</option>
                                    <option value="pro">Pro</option>
                                    <option value="maxsus">Maxsus</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Summa (so'm)</label>
                                <input
                                    v-model="paymentForm.amount"
                                    type="number"
                                    min="0"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">To'lov usuli</label>
                                <select
                                    v-model="paymentForm.method"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                >
                                    <option value="cash">Naqd</option>
                                    <option value="p2p">P2P o'tkazma</option>
                                    <option value="click" disabled>Click (tez orada)</option>
                                    <option value="payme" disabled>Payme (tez orada)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Muddat (kun)</label>
                                <input
                                    v-model="paymentForm.period_days"
                                    type="number"
                                    min="1"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">To'lov sanasi</label>
                                <input
                                    v-model="paymentForm.paid_at"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                />
                            </div>
                            <div class="flex items-end">
                                <button
                                    type="submit"
                                    :disabled="paymentForm.processing"
                                    class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50"
                                >
                                    To'lov qo'shish
                                </button>
                            </div>
                            <div v-if="Object.keys(paymentForm.errors).length" class="col-span-full text-sm text-red-600">
                                <div v-for="(error, key) in paymentForm.errors" :key="key">{{ error }}</div>
                            </div>
                        </form>

                        <!-- To'lovlar tarixi -->
                        <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-gray-100">To'lovlar tarixi</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                                        <th class="py-2 pr-4">Sana</th>
                                        <th class="py-2 pr-4">Tarif</th>
                                        <th class="py-2 pr-4">Summa</th>
                                        <th class="py-2 pr-4">Usul</th>
                                        <th class="py-2 pr-4">Muddat</th>
                                        <th class="py-2 pr-4">Kim qo'shdi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="payment in workshop.subscription_payments" :key="payment.id">
                                        <td class="py-2 pr-4 text-gray-900 dark:text-white">{{ formatDate(payment.paid_at) }}</td>
                                        <td class="py-2 pr-4 text-gray-900 dark:text-white">{{ planLabels[payment.plan] || payment.plan }}</td>
                                        <td class="py-2 pr-4 text-gray-900 dark:text-white">{{ formatMoney(payment.amount) }} so'm</td>
                                        <td class="py-2 pr-4 text-gray-900 dark:text-white">{{ methodLabels[payment.method] || payment.method }}</td>
                                        <td class="py-2 pr-4 text-gray-900 dark:text-white">{{ payment.period_days }} kun</td>
                                        <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ payment.confirmed_by?.name || '-' }}</td>
                                    </tr>
                                    <tr v-if="!workshop.subscription_payments?.length">
                                        <td colspan="6" class="py-4 text-center text-gray-500 dark:text-gray-400">Hali to'lovlar yo'q</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
