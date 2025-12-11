<script setup lang="ts">
import { SidebarProvider } from '@/components/layout/sidebar';

defineProps<{
  variant?: 'header' | 'sidebar';
}>();
</script>

<template>
  <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
    <slot />
  </div>
  <SidebarProvider v-else>
    <slot />
  </SidebarProvider>
</template>

