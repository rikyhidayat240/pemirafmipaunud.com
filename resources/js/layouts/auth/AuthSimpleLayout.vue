<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div className='border-secondary border-2 rounded-lg shadow-xl dark:shadow-secondary sm:min-w-lg flex justify-center p-8'>
            <div class="w-full max-w-sm">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col items-center gap-4">
                        <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                        <div class="mb-1 flex h-8 w-8 items-center justify-center rounded-md">
                            <AppLogoIcon class="size-8 fill-current text-foreground dark:text-white" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                        </Link>
                        <div class="space-y-2 text-center">
                            <h1 class="text-xl font-semibold">{{ title }}</h1>
                            <p class="text-center text-sm text-muted-foreground">{{ description }}</p>
                        </div>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
