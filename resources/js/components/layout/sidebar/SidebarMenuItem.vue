<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useSidebar } from './utils';

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { open, isMobile } = useSidebar();
</script>

<template>
  <li
    data-slot="sidebar-menu-item"
    data-sidebar="menu-item"
    :class="[cn('group/menu-item relative', props.class), { 'w-full': isMobile || open }]"
  >
    <slot />
  </li>
</template>
