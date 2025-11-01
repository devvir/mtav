<script setup lang="ts">
// Copilot - pending review
import PersonCard from '../shared/PersonCard.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  family: Family & {
    members_count: number;
  };
}>();
</script>

<template>
  <PersonCard
    :href="`/families/${family.id}`"
    :avatar="family.avatar"
    :name="family.name"
    :subtitle="`${family.members_count} ${family.members_count === 1 ? _('member') : _('members')}`"
  />
</template>
