<script setup lang="ts">
import { cn } from '@/lib/utils'
import { AvatarFallback, type AvatarFallbackProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'

const props = defineProps<AvatarFallbackProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <AvatarFallback
    data-slot="avatar-fallback"
    v-bind="delegatedProps"
    :class="cn('bg-surface-interactive text-text flex size-full items-center justify-center rounded-full font-semibold', props.class)"
  >
    <slot />
  </AvatarFallback>
</template>
