<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <main :class="cn(
    'flex flex-1 flex-col items-start gap-4',
    $props.class,
    '@container/card-content',
    'relative min-w-0'
  )">
    <slot />
  </main>
</template>
