<script setup lang="ts">
defineProps<{
  height?: string;
}>();
</script>

<template>
  <div
    :class="[
      'animate-pulse rounded-lg border border-border-subtle bg-surface-sunken',
      height || 'h-20',
    ]"
  />
</template>
