<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <h4 :class="cn('text-xs text-text-subtle', $props.class, 'truncate min-w-0')">
    <slot />
  </h4>
</template>