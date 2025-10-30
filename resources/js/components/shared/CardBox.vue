<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: string;
}>();

const attrs = useAttrs();
</script>

<template>
  <div
    :class="
      cn(
        'rounded-xl border border-border bg-surface-elevated text-surface-elevated-foreground shadow-sm transition-all hover:shadow-md focus-within:ring-2 focus-within:ring-focus-ring focus-within:ring-offset-2 focus-within:ring-offset-focus-ring-offset',
        props.class,
      )
    "
    v-bind="attrs"
  >
    <div v-if="$slots.header" class="mb-wide-y border-b border-border-subtle pb-wide-y">
      <slot name="header" />
    </div>
    <slot />
  </div>
</template>
