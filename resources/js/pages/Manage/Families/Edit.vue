<script setup lang="ts">
import FamilyForm from '@/components/forms/FamilyForm.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Family, UnitType } from '@/types/models';

defineProps<{ family: Family; unitTypes: UnitType[] }>();
</script>

<template>
  <PageHeader
    :title="family.name"
    :back="{ href: route('manage.families.index'), label: _('Families') }"
  />
  <FamilyForm :family :unit-types />
</template>
