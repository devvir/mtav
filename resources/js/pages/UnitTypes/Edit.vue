<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  unit_type: ApiResource<UnitType>;
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
      type="update"
      :action="route('unit_types.update', unit_type.id)"
      :title="_('Edit Unit Type')"
      :unit_type="unit_type"
    />
  </MaybeModal>
</template>
