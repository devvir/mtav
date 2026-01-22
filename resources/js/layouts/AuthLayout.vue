<script setup lang="ts">
import FlashMessages from '@/components/flash/FlashMessages.vue';
import { _ } from '@/composables/useTranslations';
import AuthIntro from '@/layouts/auth/AuthIntro.vue';

const props = defineProps<{
  title?: string;
  description?: string;
  wide?: boolean;
}>();

const maxWidthClass = computed(() => (props.wide ? 'max-w-4xl' : 'max-w-md'));
</script>

<template>
  <div class="flex min-h-svh flex-col bg-background lg:flex-row">
    <!-- Left side - Intro section -->
    <AuthIntro />

    <!-- Right side - Content -->
    <div class="flex flex-1 items-center justify-center px-4 py-12 lg:px-12">
      <div class="w-full space-y-8" :class="maxWidthClass">
        <!-- Form header -->
        <div v-if="title" class="hidden space-y-2 text-center sm:block lg:text-left">
          <h2 class="text-xl font-semibold tracking-tight sm:text-2xl">
            {{ _(title) }}
          </h2>
          <p v-if="description" class="text-sm text-muted-foreground sm:text-base">
            {{ _(description) }}
          </p>
        </div>

        <FlashMessages no-auto-dismiss multiline />

        <slot />
      </div>
    </div>
  </div>
</template>
