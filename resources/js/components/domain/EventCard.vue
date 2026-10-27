<script setup lang="ts">
import Badge from '@/components/feedback/Badge.vue';
import DateTime from '@/components/feedback/DateTime.vue';
import { Button } from '@/components/ui/button';
import { route } from '@/composables/useRoute';
import { _, languageTag as lang } from '@/composables/useTranslations';
import type { ProjectEvent } from '@/types/models';
import { Link, router } from '@inertiajs/vue3';
import { Check, MapPin, Video, X } from 'lucide-vue-next';

/**
 * An event in a list, with one-tap attendance when it asks for it.
 */
const props = defineProps<{ event: ProjectEvent; href?: string }>();

const rsvp = (attending: boolean) =>
  router.put(
    route('events.rsvp', props.event.id),
    { attending },
    { preserveScroll: true, only: ['events', 'event', 'flash'] },
  );
</script>

<template>
  <article
    class="flex flex-col gap-3 rounded-2xl border-2 border-border bg-surface p-4 sm:flex-row sm:items-start"
  >
    <div
      class="flex w-16 shrink-0 flex-col items-center rounded-xl bg-surface-sunken py-2 text-center"
      aria-hidden="true"
    >
      <template v-if="event.start_date">
        <span class="text-xs font-semibold text-text-muted uppercase">{{
          new Date(event.start_date).toLocaleString(lang, { month: 'short' })
        }}</span>
        <span class="text-2xl font-bold">{{ new Date(event.start_date).getDate() }}</span>
      </template>
      <span v-else class="py-2 text-xs font-semibold text-text-muted">{{ _('TBD') }}</span>
    </div>

    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-2">
        <Link
          :href="href ?? route('events.show', event.id)"
          class="text-lg font-semibold hover:text-text-link"
          >{{ event.title }}</Link
        >
        <Badge v-if="!event.is_published" tone="warning">{{ _('Draft') }}</Badge>
        <Badge v-if="event.status === 'ongoing'" tone="success">{{ _('Happening now') }}</Badge>
        <Badge v-if="event.status === 'completed'">{{ _('Past') }}</Badge>
      </div>
      <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-text-muted">
        <DateTime v-if="event.start_date" :value="event.start_date" />
        <span v-if="event.location" class="flex min-w-0 items-center gap-1">
          <component :is="event.type === 'online' ? Video : MapPin" class="size-4 shrink-0" />
          <span class="truncate">{{ event.type === 'online' ? _('Online') : event.location }}</span>
        </span>
      </p>
    </div>

    <div v-if="event.can.rsvp && event.status !== 'completed'" class="flex gap-2 sm:flex-col">
      <Button
        size="sm"
        :variant="event.attending === true ? 'default' : 'outline'"
        :aria-pressed="event.attending === true"
        @click="rsvp(true)"
      >
        <Check /> {{ _("I'll go") }}
      </Button>
      <Button
        size="sm"
        :variant="event.attending === false ? 'secondary' : 'outline'"
        :aria-pressed="event.attending === false"
        @click="rsvp(false)"
      >
        <X /> {{ _("I can't") }}
      </Button>
    </div>
  </article>
</template>
