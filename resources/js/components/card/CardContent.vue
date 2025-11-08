<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: string;
}>();
</script>

<template>
  <main
    :class="cn(
      'px-6 py-4 flex-grow',
      'min-w-0', // Critical for content truncation
      props.class
    )"
  >
    <slot />
  </main>
</template>