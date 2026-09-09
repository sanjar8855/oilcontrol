<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import OnboardingExitLink from '@/Components/OnboardingExitLink.vue';

defineProps({
    step: {
        type: Number,
        required: true,
    },
});
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900">
        <div class="flex items-center gap-3">
            <ApplicationLogo class="h-16 w-16 fill-current text-gray-500" />
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ step }}/3</span>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-6 shadow-md sm:max-w-lg sm:rounded-lg dark:bg-gray-800">
            <slot />
        </div>

        <div class="mt-4">
            <OnboardingExitLink />
        </div>
    </div>
</template>
