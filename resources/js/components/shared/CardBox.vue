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
        'rounded-xl border-t-1 border-muted-foreground/25 bg-muted shadow shadow-muted-foreground/20 hover:bg-muted/95',
        props.class,
      )
    "
    v-bind="attrs"
  >
    <div v-if="$slots.header" class="mb-wide-y border-b border-foreground/10 pb-wide-y">
      <slot name="header" />
    </div>
    <slot />
  </div>
</template>
