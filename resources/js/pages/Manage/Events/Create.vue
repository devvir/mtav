<script setup lang="ts">
import EventForm from '@/components/forms/EventForm.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
</script>

<template>
  <PageHeader
    :title="_('Create an event')"
    :back="{ href: route('manage.events.index'), label: _('Events') }"
  />
  <EventForm />
</template>
