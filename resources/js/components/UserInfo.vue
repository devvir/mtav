<script setup lang="ts">
import { Avatar } from '@/components/avatar';

defineProps<{
  user: ApiResource<User>;
  showEmail?: boolean;
}>();
</script>

<template>
  <Avatar :subject="user" size="sm" :class="user.is_admin ? 'rounded-lg' : 'rounded-full'" />

  <div class="grid flex-1 text-left text-sm leading-tight">
    <span class="truncate font-medium">{{ user.name }}</span>
    <span v-if="showEmail" class="truncate text-xs text-text-muted">{{ user.email }}</span>
  </div>
</template>
