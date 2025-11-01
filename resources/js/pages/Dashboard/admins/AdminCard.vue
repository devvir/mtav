<script setup lang="ts">
import PersonCard from '../shared/PersonCard.vue';

defineProps<{
  admin: Admin;
}>();
</script>

<template>
  <PersonCard
    :href="`/admins/${admin.id}`"
    :avatar="admin.avatar"
    :name="admin.name"
    :subtitle="admin.email"
  />
</template>
