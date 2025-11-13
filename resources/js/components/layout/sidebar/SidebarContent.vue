<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

defineProps<{
  class?: HTMLAttributes['class'];
}>()
</script>

<template>
  <div
    data-slot="sidebar-content"
    data-sidebar="content"
    :class="cn(
      'flex flex-1 flex-col gap-2 overflow-auto group-data-[collapsible=icon]:overflow-hidden',
      $props.class
    )"
  >
    <slot />
  </div>
</template>
