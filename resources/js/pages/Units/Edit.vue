<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

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
    <Breadcrumb route="units.show" :params="unit.id"
      >{{ _('Unit') }} {{ unit.identifier }}</Breadcrumb
    >
    <Breadcrumb route="units.edit" :params="unit.id" text="Edit" />
  </Breadcrumbs>

  <CreateUpdate
    type="update"
    action="units.update"
    :title="`${_('Edit Unit')}: ${unit.identifier}`"
    v-bind="$props"
  />
</template>
