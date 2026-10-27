<script setup lang="ts">
import UnitForm from '@/components/forms/UnitForm.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Unit, UnitType } from '@/types/models';

defineProps<{ unit: Unit; unitTypes: UnitType[] }>();
</script>

<template>
  <PageHeader
    :title="unit.identifier"
    :back="{ href: route('manage.units.index'), label: _('Units') }"
  />
  <UnitForm :unit :unit-types />
</template>
