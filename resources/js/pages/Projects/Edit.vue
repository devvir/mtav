<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  project: ApiResource<Project>;
  admins: ApiResource<Admin>[];
}>();
</script>

<template>
  <Head title="Update Project" />

  <Breadcrumbs global>
    <Breadcrumb route="projects.index" text="Projects" />
    <Breadcrumb route="projects.show" :params="project.id">{{ project.name }}</Breadcrumb>
    <Breadcrumb route="projects.edit" :params="project.id" text="Edit" />
  </Breadcrumbs>

  <CreateUpdate
    :title="_('Update Project')"
    type="edit"
    action="projects.update"
    class="mx-auto size-full max-w-2xl"
    :project
    :admins
  />
</template>
