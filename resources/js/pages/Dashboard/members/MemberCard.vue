<script setup lang="ts">
// Copilot - pending review
import PersonCard from '../shared/PersonCard.vue';

defineProps<{
  member: Member & {
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
    :name="member.name"
    :subtitle="member.family?.name"
    rounded="full"
  />
</template>
