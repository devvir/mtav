<script setup lang="ts">
defineProps<{
  title?: string;
  fallbackText?: string;
}>();
</script>

<template>
  <div class="min-h-38 w-full flex-1 rounded-lg border border-border bg-surface/15 p-3">
    <h4 v-if="title" class="mb-2 text-sm font-medium text-text">{{ title }}</h4>
    <p
      class="line-clamp-12 text-sm"
      :class="$slots.default ? 'text-text-muted' : 'text-text-subtle'"
    >
      <slot>{{ fallbackText }}</slot>
    </p>
  </div>
</template>
