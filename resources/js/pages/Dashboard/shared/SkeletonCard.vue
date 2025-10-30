<script setup lang="ts">
defineProps<{
  height?: string;
}>();
</script>

<template>
  <div :class="['animate-pulse rounded-lg bg-muted/50', height || 'h-20']" />
</template>
