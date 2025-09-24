<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from '@/pages/Families/Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  projects: Project[];
}>();
</script>

<template>
  <Head title="New Family" />

  <Breadcrumbs>
    <Breadcrumb route="families.index" text="Families" />
    <Breadcrumb route="families.create" text="New Family" />
  </Breadcrumbs>

  <CreateUpdate
    :title="_('Register a new Family')"
    type="create"
    action="families.store"
    class="mx-auto size-full max-w-2xl"
    :projects="projects"
  />
</template>
