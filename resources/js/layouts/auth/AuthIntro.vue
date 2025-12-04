<script setup lang="ts">
import IntroCarousel from '@/layouts/auth/IntroCarousel.vue';
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <div class="relative flex flex-col items-center justify-center gap-2 bg-linear-to-br from-primary/5 to-primary/10 px-4 py-6 sm:gap-4 sm:py-8 lg:w-1/2 lg:gap-6 lg:px-12 lg:py-16">
    <!-- Carousel -->
    <div class="w-full max-w-lg pb-1">
      <IntroCarousel />
    </div>

    <!-- Logo and Description -->
    <div class="mx-base flex max-w-lg items-center gap-3 sm:gap-4">
      <div class="w-10 shrink-0 opacity-70">
        <img src="/images/main-icon.svg" alt="MTAV Logo" class="h-auto w-full" />
      </div>

      <p class="flex-1 text-sm leading-relaxed tracking-wide text-muted-foreground">
        {{ _('MTAV facilites a fair distribution of housing units among families in cooperative housing projects.') }}
      </p>
    </div>
  </div>
</template>
