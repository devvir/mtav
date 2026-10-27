<script setup lang="ts">
import FamilyForm from '@/components/forms/FamilyForm.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { UnitType } from '@/types/models';

defineProps<{ unitTypes: UnitType[] }>();
</script>

<template>
  <PageHeader
    :title="_('Add a family')"
    :back="{ href: route('manage.families.index'), label: _('Families') }"
  />
  <FamilyForm :unit-types />
</template>
