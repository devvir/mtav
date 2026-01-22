<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const search = defineModel<string>({ default: '' });

const emit = defineEmits<{
  input: [value: string];
}>();

defineProps<{
  autofocus?: boolean;
  placeholder?: string;
  class?: HTMLAttributes['class'];
}>();

watchDebounced(search, (value: string) => emit('input', value), { debounce: 300, maxWait: 1000 });
</script>

<template>
  <div :class="cn('flex-1', $props.class)">
    <input
      type="search"
      :autofocus="autofocus"
      v-model.trim="search"
      class="w-full rounded-xl border border-border bg-background/80 px-base py-1 text-lg text-text placeholder-text-muted/50 shadow-sm outline-0 backdrop-blur-sm focus:placeholder-transparent focus:ring-2 focus:ring-focus-ring hocus:bg-background/90"
      :placeholder="_(placeholder ?? 'Search...')"
    />
  </div>
</template>
