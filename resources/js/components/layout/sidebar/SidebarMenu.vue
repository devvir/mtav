<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { useSidebar } from './utils';

defineProps<{
  class?: HTMLAttributes['class'];
}>();

const { open: isSidebarOpen } = useSidebar();
</script>

<template>
  <ul
    data-slot="sidebar-menu"
    data-sidebar="menu"
    :class="[
      cn('flex w-full min-w-0 flex-col gap-1', $props.class),
      { 'items-center': !isSidebarOpen },
    ]"
  >
    <slot />
  </ul>
</template>
