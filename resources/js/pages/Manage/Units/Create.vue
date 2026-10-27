<script setup lang="ts">
import UnitForm from '@/components/forms/UnitForm.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { UnitType } from '@/types/models';

defineProps<{ unitTypes: UnitType[]; unitType: number | null }>();
</script>

<template>
  <PageHeader
    :title="_('Add units')"
    :back="{ href: route('manage.units.index'), label: _('Units') }"
  />
  <UnitForm :unit-types :unit-type />
</template>
