<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{ title: string; message?: string; icon?: Component }>();
</script>

<template>
  <div
    class="flex flex-col items-center rounded-2xl border-2 border-dashed border-border px-6 py-12 text-center"
  >
    <component :is="icon" v-if="icon" class="mb-3 size-10 text-text-subtle" />
    <p class="text-lg font-semibold">{{ title }}</p>
    <p v-if="message" class="mt-1 max-w-md text-text-muted">{{ message }}</p>
    <div v-if="$slots.default" class="mt-5"><slot /></div>
  </div>
</template>
