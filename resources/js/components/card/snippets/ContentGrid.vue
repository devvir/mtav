<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

defineProps<{
  minWidth?: string;
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <div
    :style="{ '--grid-content-min': minWidth }"
    :class="cn(
      'grid gap-4 @sm:grid-cols-[repeat(auto-fit,minmax(var(--grid-content-min,20cqw),1fr))] w-full',
      $props.class
    )"
  >
    <slot />
  </div>
</template>