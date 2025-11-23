<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import type { OptionValue } from './types';

const selected = defineModel<OptionValue>();

const emit = defineEmits<{
  input: [value: OptionValue];
}>();

defineProps<{
  options: Record<OptionValue, string>;
  class?: HTMLAttributes['class'];
}>();

const selectOption = (value: OptionValue) => {
  selected.value = value;
  emit('input', value);
};
</script>

<template>
  <div :class="cn(
    'grid grid-cols-2 overflow-hidden rounded-xl border-2 border-border bg-surface/50 backdrop-blur-sm',
    $props.class
  )">
    <button
      v-for="(label, value) in options"
      :key="value"
      type="button"
      class="flex items-center justify-center px-4 py-1 text-sm font-medium transition-all duration-100 whitespace-nowrap"
      :class="
        (selected === value)
          ? 'bg-primary text-primary-foreground shadow-sm'
          : 'text-text-muted hover:text-text hover:bg-surface-elevated'
      "
      @click="selectOption(value)"
    >
      {{ _(label) }}
    </button>
  </div>
</template>