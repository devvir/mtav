<script setup lang="ts">
import { _ } from '@/composables/useTranslations';

defineProps<{
  forId: string;
  label: string;
  disabled?: boolean;
  required?: boolean;
}>();
</script>

<template>
  <label
    v-if="label"
    :for="forId"
    class="relative flex h-full grid-cols-subgrid items-center"
    :title="required ? _('This field is required') : ''"
    :class="
      required && !disabled
        ? `after:relative after:-top-2 after:m-1 after:translate-y-1 after:text-xs after:text-foreground/40 after:content-['✱'] after:text-shadow-danger-foreground/25 after:text-shadow-md`
        : ''
    "
  >
    {{ _(label) }}
  </label>
</template>
