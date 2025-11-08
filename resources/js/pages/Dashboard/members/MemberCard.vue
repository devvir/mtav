<script setup lang="ts">
import PersonCard from '../shared/PersonCard.vue';

defineProps<{
  member: Member & { family: Family };
}>();
</script>

<template>
  <PersonCard
    :href="route('members.show', member.id)"
    :subject="member"
    :subtitle="member.family.name"
    rounded="full"
  />
</template>
