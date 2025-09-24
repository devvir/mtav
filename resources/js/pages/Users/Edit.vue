<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineProps<{
  user: User;
}>();
</script>

<template>
  <Head title="Update Member" />

  <Breadcrumbs>
    <Breadcrumb route="users.index" text="Members" />
    <Breadcrumb route="users.show" :params="user.id">{{ user.name }}</Breadcrumb>
    <Breadcrumb route="users.update" :params="user.id" text="Edit" />
  </Breadcrumbs>

  <CreateUpdate
    title="Update Member"
    type="edit"
    action="users.update"
    class="mx-auto size-full max-w-2xl"
    :user="user"
    :families="[user.family]"
    :projects="user.projects as Project[]"
  />
</template>
