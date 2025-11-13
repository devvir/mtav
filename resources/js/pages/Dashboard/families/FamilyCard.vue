<script setup lang="ts">
import { entityLabel } from '@/composables/useResources';
import PersonCard from '../shared/PersonCard.vue';

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
