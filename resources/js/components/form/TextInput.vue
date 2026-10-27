<script setup lang="ts">
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const model = defineModel<string | number | null>();
</script>

<template>
  <input
    v-bind="$attrs"
    v-model="model"
    :class="
      cn(
        'block min-h-11 w-full rounded-lg border-2 border-border bg-surface px-3 py-2 text-base text-text shadow-xs transition outline-none placeholder:text-text-subtle',
        'focus-visible:border-border-interactive focus-visible:ring-2 focus-visible:ring-focus-ring/40',
        'aria-invalid:border-error aria-invalid:ring-2 aria-invalid:ring-error/30',
        'disabled:cursor-not-allowed disabled:opacity-60',
        $attrs.class as string,
      )
    "
  />
</template>
