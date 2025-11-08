<script setup lang="ts">
import PersonCard from '../shared/PersonCard.vue';

defineProps<{
  admin: Admin;
}>();
</script>

<template>
  <PersonCard
    :href="route('admins.show', admin.id)"
    :subject="admin"
    :subtitle="admin.email"
  />
</template>
