<script setup lang="ts">
import EventCard from '@/components/domain/EventCard.vue';
import MediaGrid from '@/components/domain/MediaGrid.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { Media, ProjectEvent } from '@/types/models';
import { Deferred, Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, Dices, Home, Image } from 'lucide-vue-next';

defineProps<{
  overview: {
    description: string;
    organization: string | null;
    families_count: number;
    members_count: number;
    units_count: number;
  };
  lottery: {
    start_date: string | null;
    status: string | null;
    unit: { id: number; identifier: string } | null;
  };
  events?: ProjectEvent[];
  media?: Media[];
}>();

const project = computed(() => usePage().props.project!);

useProjectChanges({
  events: ['events'],
  media: ['media'],
  lottery_runs: ['lottery'],
  families: ['overview'],
  people: ['overview'],
});
</script>

<template>
  <PageHeader :title="project.name" :description="overview.description" />

  <div class="grid gap-6 lg:grid-cols-3">
    <!-- The lottery, the heart of the project -->
    <Link
      :href="route('lottery.show')"
      class="flex flex-col gap-2 rounded-2xl border-2 border-border bg-surface p-5 transition hover:border-border-interactive lg:col-span-1"
    >
      <Dices class="size-8 text-interactive" />
      <h2 class="text-lg font-semibold">{{ _('Lottery') }}</h2>
      <template v-if="lottery.status === 'completed'">
        <p v-if="lottery.unit" class="text-text">
          {{ _('Your family received') }}
          <strong class="text-success">{{ lottery.unit.identifier }}</strong
          >.
        </p>
        <p v-else class="text-text-muted">{{ _('The lottery is done. See the results.') }}</p>
      </template>
      <p v-else-if="lottery.status === 'running'" class="text-text-muted">
        {{ _('The lottery is running right now.') }}
      </p>
      <p v-else-if="lottery.start_date" class="text-text-muted">
        {{ _('Scheduled for') }}
        <DateTime :value="lottery.start_date" class="font-medium text-text" />.
        <template v-if="project.can.rank">{{
          _("Make sure your family's ranking is how you want it.")
        }}</template>
      </p>
      <p v-else class="text-text-muted">{{ _('Not scheduled yet.') }}</p>
    </Link>

    <section class="space-y-3 lg:col-span-2">
      <div class="flex items-center justify-between">
        <h2 class="flex items-center gap-2 text-lg font-semibold">
          <CalendarDays class="size-5" /> {{ _('Coming up') }}
        </h2>
        <Link
          :href="route('events.index')"
          class="text-sm font-medium text-text-link hover:underline"
          >{{ _('All events') }}</Link
        >
      </div>
      <Deferred data="events">
        <template #fallback
          ><div class="h-24 animate-pulse rounded-2xl bg-surface-sunken"
        /></template>
        <div v-if="events?.length" class="space-y-3">
          <EventCard v-for="event in events" :key="event.id" :event />
        </div>
        <p
          v-else
          class="rounded-2xl border-2 border-dashed border-border p-6 text-center text-text-muted"
        >
          {{ _('No upcoming events.') }}
        </p>
      </Deferred>
    </section>
  </div>

  <section class="mt-8 space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="flex items-center gap-2 text-lg font-semibold">
        <Image class="size-5" /> {{ _('Recently shared') }}
      </h2>
      <Link
        :href="route('media.index')"
        class="text-sm font-medium text-text-link hover:underline"
        >{{ _('All files') }}</Link
      >
    </div>
    <Deferred data="media">
      <template #fallback
        ><div class="h-40 animate-pulse rounded-2xl bg-surface-sunken"
      /></template>
      <MediaGrid v-if="media?.length" :media />
      <p
        v-else
        class="rounded-2xl border-2 border-dashed border-border p-6 text-center text-text-muted"
      >
        {{ _('Nothing shared yet.') }}
      </p>
    </Deferred>
  </section>

  <section class="mt-8 grid grid-cols-3 gap-3">
    <Link
      :href="route('families.index')"
      class="rounded-2xl bg-surface-sunken p-4 text-center hover:bg-surface-interactive-hover"
    >
      <p class="text-2xl font-bold">{{ overview.families_count }}</p>
      <p class="text-sm text-text-muted">{{ _('families') }}</p>
    </Link>
    <Link
      :href="route('people.index')"
      class="rounded-2xl bg-surface-sunken p-4 text-center hover:bg-surface-interactive-hover"
    >
      <p class="text-2xl font-bold">{{ overview.members_count }}</p>
      <p class="text-sm text-text-muted">{{ _('people') }}</p>
    </Link>
    <Link
      :href="route('units.index')"
      class="rounded-2xl bg-surface-sunken p-4 text-center hover:bg-surface-interactive-hover"
    >
      <p class="flex items-center justify-center gap-1 text-2xl font-bold">
        <Home class="size-5" />{{ overview.units_count }}
      </p>
      <p class="text-sm text-text-muted">{{ _('units') }}</p>
    </Link>
  </section>
</template>
