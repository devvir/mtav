<script setup lang="ts">
import { Avatar } from '@/components/avatar';
import { ModalLink } from '@inertiaui/modal-vue';

defineProps<{
  href: string;
  subject: Subject;
  subtitle?: string;
  rounded?: 'default' | 'full';
}>();
</script>

<template>
  <ModalLink
    :href="href"
    class="flex items-center gap-3 rounded-lg border border-border bg-surface-elevated p-3 transition-all hover:border-border-interactive hover:shadow-md focus:ring-2 focus:ring-focus-ring focus:ring-offset-2 focus:ring-offset-focus-ring-offset focus:outline-none"
  >
    <Avatar :subject="subject" size="lg" :class="rounded === 'full' ? 'rounded-full' : ''" />
    <div class="flex-1 overflow-hidden">
      <div class="truncate text-sm font-semibold text-text">{{ subject.name }}</div>
      <div v-if="subtitle" class="truncate text-xs text-text-muted">{{ subtitle }}</div>
    </div>
  </ModalLink>
</template>
