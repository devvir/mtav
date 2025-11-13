<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { SidebarInset } from '@/components/layout/sidebar';

defineProps<{
  variant?: 'header' | 'sidebar';
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <SidebarInset v-if="variant === 'sidebar'" :class="$props.class">
    <slot />
  </SidebarInset>

  <main v-else :class="cn(
    'mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl',
    $props.class
  )">
    <slot />
  </main>
</template>
