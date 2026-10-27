<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useProjectChanges } from '@/composables/useRealtime';
import { route } from '@/composables/useRoute';
import { _ } from '@/composables/useTranslations';
import type { ProjectEvent } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { Check, Clock, MapPin, Pencil, Video, X } from 'lucide-vue-next';

const props = defineProps<{ event: ProjectEvent }>();

const rsvp = (attending: boolean) =>
  router.put(route('events.rsvp', props.event.id), { attending }, { preserveScroll: true });

useProjectChanges({ events: ['event'] });
</script>

<template>
  <PageHeader :title="event.title" :back="{ href: route('events.index'), label: _('Events') }">
    <template v-if="event.can.update" #actions>
      <Button variant="outline" as-child
        ><Link :href="route('manage.events.edit', event.id)"
          ><Pencil /> {{ _('Edit') }}</Link
        ></Button
      >
    </template>
  </PageHeader>

  <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
    <article class="rounded-2xl border-2 border-border bg-surface p-5 sm:p-6">
      <ul class="mb-5 space-y-2 text-text-muted">
        <li v-if="event.start_date" class="flex items-center gap-2">
          <Clock class="size-5" /> <DateTime :value="event.start_date" />
          <template v-if="event.end_date"> – <DateTime :value="event.end_date" /></template>
          <Badge v-if="event.status === 'ongoing'" tone="success">{{ _('Happening now') }}</Badge>
        </li>
        <li v-if="event.location" class="flex items-center gap-2">
          <template v-if="event.type === 'online'">
            <Video class="size-5" />
            <a
              :href="event.location"
              target="_blank"
              rel="noopener"
              class="text-text-link hover:underline"
              >{{ _('Join online') }}</a
            >
          </template>
          <template v-else><MapPin class="size-5" /> {{ event.location }}</template>
        </li>
      </ul>
      <p class="whitespace-pre-line">{{ event.description }}</p>
      <p v-if="event.creator" class="mt-5 text-sm text-text-subtle">
        {{ _('Posted by {name}', { name: event.creator }) }}
      </p>
    </article>

    <aside
      v-if="event.allows_rsvp"
      class="h-fit space-y-4 rounded-2xl border-2 border-border bg-surface p-5"
    >
      <template v-if="event.can.rsvp && event.status !== 'completed'">
        <h2 class="font-semibold">{{ _('Will you go?') }}</h2>
        <div class="flex gap-2">
          <Button
            class="flex-1"
            :variant="event.attending === true ? 'default' : 'outline'"
            :aria-pressed="event.attending === true"
            @click="rsvp(true)"
            ><Check /> {{ _('Yes') }}</Button
          >
          <Button
            class="flex-1"
            :variant="event.attending === false ? 'secondary' : 'outline'"
            :aria-pressed="event.attending === false"
            @click="rsvp(false)"
            ><X /> {{ _('No') }}</Button
          >
        </div>
      </template>
      <div>
        <h2 class="font-semibold">
          {{ _('Going ({count})', { count: event.attendees?.length ?? 0 }) }}
        </h2>
        <ul class="mt-2 space-y-1 text-sm">
          <li v-for="person in event.attendees" :key="person.id">{{ person.name }}</li>
        </ul>
      </div>
    </aside>
  </div>
</template>
