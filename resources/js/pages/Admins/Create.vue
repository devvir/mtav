<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  projects: Project[];
}>();
</script>

<template>
  <Head title="New Admin" />

  <Breadcrumbs>
    <Breadcrumb route="admins.index" text="Admins" />
    <Breadcrumb route="admins.create" text="Create" />
  </Breadcrumbs>

  <CreateUpdate
    :title="_('Create a new Admin')"
    type="create"
    action="admins.store"
    class="mx-auto size-full max-w-2xl"
    :projects
  />
</template>
