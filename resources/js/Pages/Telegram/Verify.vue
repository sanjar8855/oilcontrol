<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    deepLink: String,
    status: String,
});

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('telegram.verify.code'), {
        onFinish: () => form.reset('code'),
    });
};

const resendForm = useForm({});

const resend = () => {
    resendForm.post(route('telegram.verify.resend'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Telegramda tasdiqlang" />

        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
            Telegramda tasdiqlang
        </h2>

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            Hisobingizni faollashtirish uchun Telegram botimizni oching va
            "Start" tugmasini bosing — sizga 6 xonali tasdiqlash kodi keladi.
        </p>

        <a
            :href="deepLink"
            target="_blank"
            rel="noopener noreferrer"
            class="mb-4 inline-flex w-full items-center justify-center rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-400"
        >
            Botni ochish
        </a>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="mt-4">
            <InputLabel for="code" value="Tasdiqlash kodi" />

            <TextInput
                id="code"
                type="text"
                inputmode="numeric"
                maxlength="6"
                class="mt-1 block w-full tracking-widest"
                v-model="form.code"
                required
                autofocus
                placeholder="123456"
            />

            <InputError class="mt-2" :message="form.errors.code" />

            <div class="mt-4 flex items-center justify-between">
                <button
                    type="button"
                    :disabled="resendForm.processing"
                    @click="resend"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Kodni qayta yuborish
                </button>

                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Tasdiqlash
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-6 text-center">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
            >
                Chiqish
            </Link>
        </div>
    </GuestLayout>
</template>
