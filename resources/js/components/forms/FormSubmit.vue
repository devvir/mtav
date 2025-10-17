<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <button
    type="submit"
    class="ml-auto cursor-pointer rounded-md bg-accent px-5 py-2 font-semibold text-accent-foreground hover:bg-accent disabled:opacity-30"
  >
    <slot>{{ _('Submit') }}</slot>
  </button>
</template>
