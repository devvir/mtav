<script setup lang="ts">
// Copilot - pending review
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import CreateUpdate from './Crud/CreateUpdate.vue';
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <Head :title="_('Create Unit Type')" />

  <Breadcrumbs>
    <Breadcrumb route="unit-types.index" text="Unit Types" />
    <Breadcrumb route="unit-types.create" :text="_('Create')" />
  </Breadcrumbs>

  <MaybeModal>
    <CreateUpdate type="create" :action="route('unit-types.store')" :title="_('Create Unit Type')" />
  </MaybeModal>
</template>
