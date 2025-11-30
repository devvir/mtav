<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import CreateUpdate from './Crud/CreateUpdate.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  event: Event;
  types: EventTypes;
}>();
</script>

<template>
  <Head title="Edit Event" />

  <Breadcrumbs>
    <Breadcrumb route="events.index" text="Events" />
    <Breadcrumb route="events.show" :params="event.id">{{ event.title }}</Breadcrumb>
    <Breadcrumb route="events.edit" :params="event.id" text="Edit" />
  </Breadcrumbs>

  <CreateUpdate
    title="Update Event"
    type="update"
    action="events.update"
    class="mx-auto size-full max-w-2xl"
    :event
    :types
  />
</template>
