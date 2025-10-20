<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  projects: Project[];
  families: Family[];
}>();
</script>

<template>
  <Head title="Invite Member" />

  <Breadcrumbs>
    <Breadcrumb route="members.index" text="Members" />
    <Breadcrumb route="members.create" text="Invite" />
  </Breadcrumbs>

  <CreateUpdate
    :title="_('Invite a new Member')"
    type="create"
    action="members.store"
    class="mx-auto size-full max-w-2xl"
    :families
    :projects
  />
</template>
