<script setup lang="ts">
defineProps<{
  height?: string;
}>();
</script>

<template>
  <div :class="['animate-pulse rounded-lg bg-surface-sunken border border-border-subtle', height || 'h-20']" />
</template>
