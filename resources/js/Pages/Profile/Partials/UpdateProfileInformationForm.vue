<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    phone: user.phone || '',
    phone_secondary: user.phone_secondary || '',
    email: user.email || '',
    salary: user.salary || '',
    hire_date: user.hire_date || '',
    position: user.position || '',
    employment_status: user.employment_status || 'active',
    address: user.address || '',
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Shaxsiy ma'lumotlar
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Hisobingizning shaxsiy ma'lumotlarini yangilang.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="To'liq ism" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="phone" value="Telefon raqam" />

                <TextInput
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model="form.phone"
                    required
                    placeholder="+998 90 123 45 67"
                />

                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div>
                <InputLabel for="phone_secondary" value="Qo'shimcha telefon (ixtiyoriy)" />

                <TextInput
                    id="phone_secondary"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model="form.phone_secondary"
                    placeholder="+998 90 123 45 67"
                />

                <InputError class="mt-2" :message="form.errors.phone_secondary" />
            </div>

            <div>
                <InputLabel for="email" value="Email (ixtiyoriy)" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Xodim ma'lumotlari
                </h3>
            </div>

            <div>
                <InputLabel for="position" value="Lavozim" />

                <TextInput
                    id="position"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.position"
                    placeholder="Mexanik, administrator, direktor..."
                />

                <InputError class="mt-2" :message="form.errors.position" />
            </div>

            <div>
                <InputLabel for="salary" value="Oylik maosh (so'm)" />

                <TextInput
                    id="salary"
                    type="number"
                    step="0.01"
                    class="mt-1 block w-full"
                    v-model="form.salary"
                    placeholder="5000000"
                />

                <InputError class="mt-2" :message="form.errors.salary" />
            </div>

            <div>
                <InputLabel for="hire_date" value="Ishga qabul qilingan sana" />

                <TextInput
                    id="hire_date"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.hire_date"
                />

                <InputError class="mt-2" :message="form.errors.hire_date" />
            </div>

            <div>
                <InputLabel for="employment_status" value="Ish holati" />

                <select
                    id="employment_status"
                    v-model="form.employment_status"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="active">Faol</option>
                    <option value="on_leave">Ta'tilda</option>
                    <option value="terminated">Ishdan bo'shatilgan</option>
                </select>

                <InputError class="mt-2" :message="form.errors.employment_status" />
            </div>

            <div>
                <InputLabel for="address" value="Uy manzili" />

                <textarea
                    id="address"
                    v-model="form.address"
                    rows="2"
                    placeholder="To'liq manzil..."
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                ></textarea>

                <InputError class="mt-2" :message="form.errors.address" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
