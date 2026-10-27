<script setup lang="ts">
import EventCard from '@/components/domain/EventCard.vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Paginated, ProjectEvent } from '@/types/models';
import { Link } from '@inertiajs/vue3';
import { CalendarPlus } from 'lucide-vue-next';

defineProps<{ events: Paginated<ProjectEvent> }>();

const href = (event: ProjectEvent) =>
  event.type === 'lottery' ? route('manage.lottery.show') : route('manage.events.edit', event.id);

useProjectChanges({ events: ['events'] });
</script>

<template>
  <PageHeader
    :title="_('Events')"
    :description="_('Drafts are visible here only, until published.')"
  >
    <template #actions>
      <Button as-child
        ><Link :href="route('manage.events.create')"
          ><CalendarPlus /> {{ _('Create an event') }}</Link
        ></Button
      >
    </template>
  </PageHeader>

  <div v-if="events.data.length" class="space-y-3">
    <EventCard v-for="event in events.data" :key="event.id" :event :href="href(event)" />
  </div>
  <EmptyState v-else :icon="CalendarPlus" :title="_('No events yet')" />

  <Pagination :page="events" />
</template>
