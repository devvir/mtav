<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings
defineProps<{ admin: Admin }>();
</script>

<template>
  <Head title="Update Admin" />

  <Breadcrumbs>
    <Breadcrumb route="users.index" text="Members" />
    <Breadcrumb route="users.show" :params="admin.id">{{ admin.name }}</Breadcrumb>
    <Breadcrumb route="users.update" :params="admin.id" text="Edit" />
  </Breadcrumbs>

  <CreateUpdate
    title="Update Admin"
    type="edit"
    action="admins.update"
    class="mx-auto size-full max-w-2xl"
    :admin
    :projects="admin.projects as Project[]"
  />
</template>
