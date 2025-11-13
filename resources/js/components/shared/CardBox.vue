<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <div
    :class="
      cn(
        'rounded-xl border border-border bg-surface-elevated text-surface-elevated-foreground shadow-sm transition-all focus-within:ring-2 focus-within:ring-focus-ring focus-within:ring-offset-2 focus-within:ring-offset-focus-ring-offset hover:shadow-md',
        $props.class,
      )
    "
    v-bind="$attrs"
  >
    <div v-if="$slots.header" class="mb-wide-y border-b border-border-subtle pb-wide-y">
      <slot name="header" />
    </div>
    <slot />
  </div>
</template>
