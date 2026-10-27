<script setup lang="ts">
import UnitTypeForm from '@/components/forms/UnitTypeForm.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { UnitType } from '@/types/models';

defineProps<{ unitType: UnitType }>();
</script>

<template>
  <PageHeader
    :title="unitType.name"
    :back="{ href: route('manage.unit_types.index'), label: _('Unit types') }"
  />
  <UnitTypeForm :unit-type />
</template>
