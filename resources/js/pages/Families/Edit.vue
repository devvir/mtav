<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings
defineProps<{ family: Family; projects: Project[] }>();
</script>

<template>
  <Head title="Update Family" />

  <Breadcrumbs>
    <Breadcrumb route="families.index" text="Families" />
    <Breadcrumb route="families.show" :params="family.id">{{ family.name }}</Breadcrumb>
    <Breadcrumb route="families.edit" :params="family.id" text="Edit" />
  </Breadcrumbs>

  <CreateUpdate
    :title="_('Update Family')"
    type="edit"
    action="families.update"
    class="mx-auto size-full max-w-2xl"
    :family
    :projects
  />
</template>
