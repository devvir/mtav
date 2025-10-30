<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-surface-elevated text-surface-elevated-foreground flex flex-col gap-6 rounded-xl border border-border py-6 shadow-sm transition-all hover:shadow-md focus-within:ring-2 focus-within:ring-focus-ring focus-within:ring-offset-2 focus-within:ring-offset-focus-ring-offset',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
