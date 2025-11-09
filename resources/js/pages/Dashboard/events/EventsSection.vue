<script setup lang="ts">
import { Calendar } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import SectionHeader from '../shared/SectionHeader.vue';
import SkeletonCard from '../shared/SkeletonCard.vue';
import EventCard from './EventCard.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  events?: Event[];
}>();
</script>

<template>
  <section>
    <SectionHeader :title="_('Upcoming Events')" />

    <template v-if="events">
      <template v-if="events.length > 0">
        <div class="space-y-3">
          <EventCard
            v-for="event in events"
            :key="event.id"
            :event="event"
          />
        </div>

        <div class="mt-4 text-center">
          <Link
            :href="route('events.index')"
            class="text-sm text-interactive hover:text-interactive-hover underline"
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
    </template>

    <template v-else>
      <div class="space-y-3">
        <SkeletonCard v-for="i in 3" :key="i" height="h-20" />
      </div>
    </template>
  </section>
</template>
