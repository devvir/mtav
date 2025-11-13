<script setup lang="ts">
defineProps<{
  title?: string;
  fallbackText?: string;
}>();
</script>

<template>
  <div class="flex-1 bg-surface/15 border border-border rounded-lg p-3 w-full">
    <h4 v-if="title" class="text-sm font-medium text-text mb-2">{{ title }}</h4>
    <p
      class="text-sm line-clamp-12"
      :class="$slots.default ? 'text-text-muted' : 'text-text-subtle'"
    >
      <slot>{{ fallbackText }}</slot>
    </p>
  </div>
</template>