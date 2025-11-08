<script setup lang="ts">
import PersonCard from '../shared/PersonCard.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  family: Family;
}>();
</script>

<template>
  <PersonCard
    :href="route('families.show', family.id)"
    :subject="family"
    :subtitle="`${family.members_count} ${family.members_count === 1 ? _('member') : _('members')}`"
  />
</template>
