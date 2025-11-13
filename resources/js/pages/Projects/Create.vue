<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  admins: ApiResource<Admin>[];
}>();
</script>

<template>
  <Head title="New Project" />

  <Breadcrumbs global>
    <Breadcrumb route="projects.index" text="Projects" />
    <Breadcrumb route="projects.create" text="Create" />
  </Breadcrumbs>

  <CreateUpdate
    :title="_('Create a new Project')"
    type="create"
    action="projects.store"
    class="mx-auto size-full max-w-2xl"
    :admins
  />
</template>
