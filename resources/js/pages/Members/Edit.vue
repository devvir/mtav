<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings
defineProps<{ member: Member }>();
</script>

<template>
  <Head title="Update Member" />

  <Breadcrumbs>
    <Breadcrumb route="members.index" text="Members" />
    <Breadcrumb route="members.show" :params="member.id">{{ member.name }}</Breadcrumb>
    <Breadcrumb route="members.update" :params="member.id" text="Edit" />
  </Breadcrumbs>

  <CreateUpdate
    title="Update Member"
    type="edit"
    action="members.update"
    class="mx-auto size-full max-w-2xl"
    :member
    :families="[member.family as Family]"
    :projects="[member.project!]"
  />
</template>
