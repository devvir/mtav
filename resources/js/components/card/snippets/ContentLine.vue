<script setup lang="ts">
defineProps<{
  label: string;
  value?: string;
  fallback?: string;
}>();
</script>

<template>
  <div
    v-if="value || fallback"
    class="grid grid-cols-[auto_1fr] text-sm truncate whitespace-nowrap space-x-1"
  >
    <strong class="font-semibold">{{ label }}:</strong>
    <span class="truncate">{{ value || fallback }}</span>
  </div>
</template>