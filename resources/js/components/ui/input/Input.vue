<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  defaultValue?: string | number;
  modelValue?: string | number;
  type?: string;
  class?: HTMLAttributes['class'];
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input :type
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      'file:text-text placeholder:text-text-subtle selection:bg-selection selection:text-selection-foreground bg-surface border-border flex min-h-[44px] w-full min-w-0 rounded-md border-2 px-3 py-2 text-base text-text shadow-sm transition-all outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 @md:min-h-[36px] md:text-sm',
      'focus-visible:border-border-interactive focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:ring-offset-2 focus-visible:ring-offset-focus-ring-offset',
      'aria-invalid:ring-2 aria-invalid:ring-error aria-invalid:border-error',
      props.class,
    )"
  >
</template>
