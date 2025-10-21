<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <button
    type="submit"
    class="ml-auto cursor-pointer rounded-md bg-accent px-10 py-2 text-lg text-accent-foreground opacity-90 shadow-lg hover:scale-99 hover:opacity-100 hover:shadow disabled:opacity-30"
  >
    <slot>{{ _('Submit') }}</slot>
  </button>
</template>
