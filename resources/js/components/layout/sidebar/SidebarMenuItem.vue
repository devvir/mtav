<script setup lang="ts">
import { cn } from '@/lib/utils'
import type { HTMLAttributes } from 'vue'
import { useSidebar } from './utils';

defineProps<{
  class?: HTMLAttributes['class'];
}>()

const { open, isMobile } = useSidebar();
</script>

<template>
  <li
    data-slot="sidebar-menu-item"
    data-sidebar="menu-item"
    :class="[
      cn('group/menu-item relative', $props.class),
      { 'w-full': isMobile || open }
    ]"
  >
    <slot />
  </li>
</template>
