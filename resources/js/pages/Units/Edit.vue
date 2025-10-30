<script setup lang="ts">
// Copilot - pending review
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>();
defineProps<{
  unit: ApiResource<Unit>;
  unit_types: UnitType[];
  families: Family[];
}>();
</script>

<template>
  <Head :title="_('Edit Unit')" />

  <Breadcrumbs>
    <Breadcrumb route="units.index" text="Units" />
    <Breadcrumb route="units.show" :params="unit.id">{{ _('Unit') }} {{ unit.number }}</Breadcrumb>
    <Breadcrumb route="units.edit" :params="unit.id" text="Edit" />
  </Breadcrumbs>

  <CreateUpdate type="edit" action="units.update" :title="`${_('Edit Unit')}: ${unit.number}`" v-bind="$props" />
</template>
