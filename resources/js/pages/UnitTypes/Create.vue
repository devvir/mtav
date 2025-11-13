<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings
</script>

<template>
  <Head :title="_('Create Unit Type')" />

  <Breadcrumbs>
    <Breadcrumb route="unit_types.index" text="Unit Types" />
    <Breadcrumb route="unit_types.create" :text="_('Create')" />
  </Breadcrumbs>

  <MaybeModal>
    <CreateUpdate
      type="create"
      :action="route('unit_types.store')"
      :title="_('Create Unit Type')"
    />
  </MaybeModal>
</template>
