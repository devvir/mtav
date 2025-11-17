<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { _ } from '@/composables/useTranslations';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  project?: ApiResource<Project>;
  types: EventTypes;
}>();
</script>

<template>
  <Head title="Create Event" />

  <Breadcrumbs>
    <Breadcrumb route="events.index" text="Events" />
    <Breadcrumb route="events.create" text="Create" />
  </Breadcrumbs>

  <CreateUpdate
    :title="_('Create a new Event')"
    type="create"
    action="events.store"
    class="mx-auto size-full max-w-2xl"
    :types
  />
</template>
