<script setup lang="ts">
defineProps<{
  title: string;
  subtitle: string;
}>();
</script>

<template>
  <header class="space-y-2">
    <h2 class="text-2xl font-semibold tracking-tight">{{ title }}</h2>
    <p class="text-sm text-muted-foreground">{{ subtitle }}</p>
  </header>
</template>