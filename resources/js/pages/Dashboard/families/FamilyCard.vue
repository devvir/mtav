<script setup lang="ts">
import PersonCard from '../shared/PersonCard.vue';
import { entityLabel } from '@/composables/useResources';

defineProps<{
  family: Family;
}>();
</script>

<template>
  <PersonCard
    :href="route('families.show', family.id)"
    :subject="family"
    :subtitle="`${family.members_count!} ${entityLabel('member', family.members_count!)}`"
  />
</template>
