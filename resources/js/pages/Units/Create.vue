<script setup lang="ts">
// Copilot - pending review
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>();
defineProps<{
  unit_types: UnitType[];
  families: Family[];
}>();
</script>

<template>
  <Head :title="_('New Unit')" />

  <Breadcrumbs>
    <Breadcrumb route="units.index" text="Units" />
    <Breadcrumb route="units.create" text="Create" />
  </Breadcrumbs>

  <CreateUpdate type="create" action="units.store" :title="_('Create Unit')" v-bind="$props" />
</template>
