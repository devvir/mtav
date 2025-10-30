<script setup lang="ts">
// Copilot - pending review
import PersonCard from '../shared/PersonCard.vue';

defineProps<{
  admin: {
    id: number;
    avatar?: string;
    full_name: string;
    email: string;
  };
}>();
</script>

<template>
  <PersonCard
    :href="`/admins/${admin.id}`"
    :avatar="admin.avatar"
    :name="admin.full_name"
    :subtitle="admin.email"
  />
</template>
