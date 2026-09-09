<!-- resources/js/Components/OnboardingExitLink.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({});

const exit = () => {
    if (confirm("O'qitish rejimidan chiqmoqchimisiz?")) {
        form.post(route('onboarding.skip'));
    }
};
</script>

<template>
    <button
        type="button"
        @click="exit"
        class="text-sm text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
    >
        O'qitish rejimidan chiqish
    </button>
</template>
