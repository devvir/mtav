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
    class="grid grid-cols-[auto_1fr] space-x-1 truncate text-sm whitespace-nowrap"
  >
    <strong class="font-semibold">{{ label }}:</strong>
    <span class="truncate">{{ value || fallback }}</span>
  </div>
</template>
