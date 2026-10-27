<script setup lang="ts">
import EventCard from '@/components/domain/EventCard.vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import Pagination from '@/components/feedback/Pagination.vue';
import SearchField from '@/components/feedback/SearchField.vue';
import InfoTip from '@/components/help/InfoTip.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import type { Paginated, ProjectEvent } from '@/types/models';
import { Link } from '@inertiajs/vue3';
import { CalendarX } from 'lucide-vue-next';

defineProps<{
  events: Paginated<ProjectEvent>;
  filters: { q: string | null; when: 'upcoming' | 'past' };
}>();

useProjectChanges({ events: ['events'] });

const tab = (active: boolean) =>
  cn(
    'min-h-11 rounded-lg px-4 py-2 font-medium',
    active ? 'bg-interactive text-interactive-foreground' : 'hover:bg-surface-interactive-hover',
  );
</script>

<template>
  <PageHeader :title="_('Events')">
    <template #info>
      <InfoTip :title="_('About events')" tour="member.events">
        <p>
          {{
            _(
              'Meetings, assemblies and activities, published by the admins of the project. Some ask whether you will go.',
            )
          }}
        </p>
      </InfoTip>
    </template>
  </PageHeader>

  <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <nav data-tour="events.tabs" class="flex gap-1">
      <Link
        :href="route('events.index')"
        :class="tab(filters.when === 'upcoming')"
        preserve-state
        >{{ _('Upcoming') }}</Link
      >
      <Link
        :href="route('events.index', { when: 'past' })"
        :class="tab(filters.when === 'past')"
        preserve-state
        >{{ _('Past') }}</Link
      >
    </nav>
    <SearchField :model-value="filters.q" class="sm:max-w-xs" />
  </div>

  <div data-tour="events.list" v-if="events.data.length" class="space-y-3">
    <EventCard v-for="event in events.data" :key="event.id" :event />
  </div>
  <EmptyState
    v-else
    :icon="CalendarX"
    :title="filters.when === 'past' ? _('No past events') : _('No upcoming events')"
  />

  <Pagination :page="events" />
</template>
