<script setup lang="ts">
import FlashMessages from '@/components/flash/FlashMessages.vue';
import { _ } from '@/composables/useTranslations';
import { computed } from 'vue';

const props = defineProps<{
  title?: string;
  description?: string;
  wide?: boolean;
}>();

const maxWidthClass = computed(() =>
  props.wide
    ? 'max-w-md sm:max-w-lg md:max-w-2xl lg:max-w-5xl'
    : 'max-w-md sm:max-w-lg'
);
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-4 bg-background p-4 md:gap-6 md:p-10">
        <div class="w-full" :class="maxWidthClass">
            <div class="flex flex-col gap-6 md:gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                    <div class="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                        <!-- <AppLogoIcon class="size-9 fill-current text-[var(--foreground)] dark:text-white" /> -->
                    </div>
                    <span class="sr-only">{{ _(title ?? '') }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ _(title ?? '') }}</h1>
                        <p class="text-center text-sm text-text-muted">{{ _(description ?? '') }}</p>
                    </div>
                </div>

                <FlashMessages no-auto-dismiss />

                <slot />
            </div>
        </div>
    </div>
</template>
