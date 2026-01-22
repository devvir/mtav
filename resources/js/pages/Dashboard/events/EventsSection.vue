<script setup lang="ts">
// Copilot - Pending review
import { can } from '@/composables/useAuth';
import { _ } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';
import { Calendar } from 'lucide-vue-next';
import SectionHeader from '../shared/SectionHeader.vue';
import EventCard from './EventCard.vue';

defineProps<{
  events: Event[];
  totalCount: number;
}>();
</script>

<template>
  <section>
    <SectionHeader
      :title="_('Upcoming Events')"
      view-all-href="events.index"
      create-href="events.create"
      :create-if="can.create('events')"
      :create-label="_('Create Event')"
    />

    <template v-if="events.length > 0">
      <div class="space-y-3">
        <EventCard v-for="event in events" :key="event.id" :event="event" />
      </div>

      <div class="mt-4 text-center" v-if="totalCount > events.length">
        <Link
          :href="route('events.index')"
          class="text-sm text-interactive underline hover:text-interactive-hover"
        >
          {{ _('View all Events') }}
        </Link>
      </div>
    </template>

    <template v-else>
      <div class="flex h-64 items-center justify-center rounded-lg">
        <div class="text-center text-sm text-text-muted">
          <Calendar class="mx-auto mb-2 h-8 w-8 opacity-50" />
          <p>{{ _('No Events scheduled') }}</p>
        </div>
      </div>
    </template>
  </section>
</template>
