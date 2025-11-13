<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <button
    type="submit"
    class="ml-auto min-h-[44px] cursor-pointer rounded-md bg-interactive px-10 py-2 text-lg text-interactive-foreground shadow-lg transition-all hover:bg-interactive-hover hover:shadow focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:outline-0 active:bg-interactive-active disabled:cursor-not-allowed disabled:opacity-40 @md:min-h-[36px]"
  >
    <slot>{{ _('Submit') }}</slot>
  </button>
</template>
