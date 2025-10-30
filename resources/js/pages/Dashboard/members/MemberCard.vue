<script setup lang="ts">
// Copilot - pending review
import PersonCard from '../shared/PersonCard.vue';

defineProps<{
  member: {
    id: number;
    avatar?: string;
    full_name: string;
    family?: {
      name: string;
    };
  };
}>();
</script>

<template>
  <PersonCard
    :href="`/members/${member.id}`"
    :avatar="member.avatar"
    :name="member.full_name"
    :subtitle="member.family?.name"
    rounded="full"
  />
</template>
