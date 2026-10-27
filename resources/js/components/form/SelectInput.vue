<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';

/**
 * A native select: the most usable choice on phones, and accessible as is.
 */
defineOptions({ inheritAttrs: false });

defineProps<{
  options: { value: string | number; label: string }[];
  placeholder?: string;
}>();

const model = defineModel<string | number | null>();
</script>

<template>
  <div class="relative">
    <select
      v-bind="$attrs"
      v-model="model"
      :class="
        cn(
          'block min-h-11 w-full appearance-none rounded-lg border-2 border-border bg-surface py-2 pr-10 pl-3 text-base text-text shadow-xs transition outline-none',
          'focus-visible:border-border-interactive focus-visible:ring-2 focus-visible:ring-focus-ring/40',
          'aria-invalid:border-error aria-invalid:ring-2 aria-invalid:ring-error/30',
          $attrs.class as string,
        )
      "
    >
      <option v-if="placeholder" :value="null" disabled>{{ placeholder }}</option>
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
    <ChevronDown
      class="pointer-events-none absolute top-1/2 right-3 size-5 -translate-y-1/2 text-text-muted"
    />
  </div>
</template>
