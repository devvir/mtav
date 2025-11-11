<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import CreateUpdate from './Crud/CreateUpdate.vue';
import { _ } from '@/composables/useTranslations';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  unit_type: UnitType;
}>();
</script>

<template>
  <Head :title="_('Edit Unit Type')" />

  <Breadcrumbs>
    <Breadcrumb route="unit_types.index" text="Unit Types" />
    <Breadcrumb route="unit_types.show" :params="unit_type.id" :text="unit_type.name" />
    <Breadcrumb route="unit_types.edit" :params="unit_type.id" :text="_('Edit')" />
  </Breadcrumbs>

  <MaybeModal>
    <CreateUpdate
      type="edit"
      :action="route('unit_types.update', unit_type.id)"
      :title="_('Edit Unit Type')"
      :unit_type="unit_type"
    />
  </MaybeModal>
</template>
