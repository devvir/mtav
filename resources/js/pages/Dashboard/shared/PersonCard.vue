<script setup lang="ts">
// Copilot - pending review
import { ModalLink } from '@inertiaui/modal-vue';
import Avatar from '@/components/Avatar.vue';

defineProps<{
  href: string;
  avatar?: string;
  name: string;
  subtitle?: string;
  rounded?: 'default' | 'full';
}>();
</script>

<template>
  <ModalLink
    :href="href"
    class="flex items-center gap-3 rounded-lg border border-sidebar-border/70 bg-card p-3 transition-all hover:shadow-md"
  >
    <Avatar
      :src="avatar"
      :name="name"
      size="md"
      :class="rounded === 'full' ? 'rounded-full' : ''"
    />
    <div class="flex-1 overflow-hidden">
      <div class="truncate text-sm font-semibold">{{ name }}</div>
      <div v-if="subtitle" class="truncate text-xs text-muted-foreground">{{ subtitle }}</div>
    </div>
  </ModalLink>
</template>
