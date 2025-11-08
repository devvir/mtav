<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: string;
}>();
</script>

<template>
  <footer
    :class="cn(
      'px-6 py-4 text-sm text-text-subtle',
      'border-t border-border',
      props.class
    )"
  >
    <slot />
  </footer>
</template>