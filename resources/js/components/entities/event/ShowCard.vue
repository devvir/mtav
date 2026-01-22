<script setup lang="ts">
import { BadgeGroup } from '@/components/badge';
import { CardContent, CardFooter, CardHeader, EntityCard } from '@/components/card';
import { ContentDetail, ContentGrid, ContentHighlight } from '@/components/card/snippets';
import { fromUTC } from '@/composables/useDates';
import { _ } from '@/composables/useTranslations';
import { CalendarIcon, ClockIcon, MapPinIcon, UserIcon } from 'lucide-vue-next';
import EventBadge from './badges/EventBadge.vue';
import { useEventBadges } from './badges/useEventBadges';

const props = defineProps<{
  event: ApiResource<Event>;
}>();

const { badges } = useEventBadges(props.event);

// Check if current user has RSVP status
const userRsvpStatus = computed(() => {
  if (!props.event.allows_rsvp || !props.event.user_rsvp) {
    return null;
  }
  return props.event.user_rsvp.status;
});

// Get RSVP status content with styling
const userRsvpContent = computed(() => {
  if (!userRsvpStatus.value) return null;
  return _(userRsvpStatus.value);
});

// Get content class for RSVP status
const userRsvpContentClass = computed(() => {
  if (!userRsvpStatus.value) return '';
  const baseClass = 'font-medium capitalize';
  if (userRsvpStatus.value === 'accepted') return `${baseClass} text-emerald-600`;
  if (userRsvpStatus.value === 'declined') return `${baseClass} text-rose-600`;
  return baseClass;
});
</script>

<template>
  <EntityCard :resource="event" entity="event" type="show">
    <CardHeader :title="event.title">
      <BadgeGroup class="mt-3">
        <EventBadge v-for="badge in badges" :key="badge.text" :config="badge" />
      </BadgeGroup>
    </CardHeader>

    <CardContent class="space-y-6">
      <!-- Location -->
      <ContentDetail
        :icon="MapPinIcon"
        :title="event.is_online ? _('Meeting Link') : _('Location')"
        :content="event.location"
        :fallback="_('Not provided')"
        :href="event.is_online && event.location ? event.location : undefined"
      />

      <!-- Event Dates & Time -->
      <ContentGrid>
        <ContentDetail
          :icon="CalendarIcon"
          :title="_('Start Date')"
          :content="event.start_date ? fromUTC(event.start_date) : undefined"
          :fallback="_('Not set')"
        />

        <ContentDetail
          v-if="event.end_date"
          :icon="ClockIcon"
          :title="_('End Date')"
          :content="fromUTC(event.end_date)"
        />
      </ContentGrid>

      <ContentGrid min-width="10cqw">
        <!-- Event Organizer -->
        <ContentDetail
          :icon="UserIcon"
          :title="_('Organizer')"
          :content="event.creator?.name"
          :fallback="_('System')"
        />

        <!-- RSVP Counts -->
        <ContentDetail
          v-if="event.allows_rsvp"
          :icon="ClockIcon"
          :title="_('RSVPs')"
          :content="
            _('{accepted} confirmed ({declined} declined)', {
              accepted: event.accepted_count || 0,
              declined: event.declined_count || 0,
              total: event.rsvps_count || 0,
            })
          "
        />
      </ContentGrid>

      <!-- Description -->
      <ContentHighlight v-if="event.description" :title="_('Description')">
        {{ event.description }}
      </ContentHighlight>

      <!-- RSVP Information -->
      <div v-if="event.allows_rsvp && event.rsvps_count" class="space-y-4">
        <!-- Current User RSVP Status -->
        <ContentDetail
          v-if="userRsvpStatus"
          :icon="UserIcon"
          :title="_('Your RSVP')"
          :content="userRsvpContent"
          :content-class="userRsvpContentClass"
        />
      </div>
    </CardContent>

    <CardFooter />
  </EntityCard>
</template>
