<script setup lang="ts">
defineProps<{
  error?: string;
}>();
</script>

<template>
  <div class="relative">
    <div
      class="absolute -top-1 w-full rounded-b bg-error px-2 pt-1 text-sm leading-tight truncate text-error-foreground transition-all empty:invisible"
      :class="{ 'scale-0 opacity-0': !error }"
      :title="error"
    >
      {{ error }}
    </div>
  </div>
</template>
