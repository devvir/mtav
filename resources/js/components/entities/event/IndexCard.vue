<script setup lang="ts">
import { BadgeGroup } from '@/components/badge';
import { EntityCard, CardContent, CardFooter, CardHeader } from '@/components/card';
import EventBadge from './badges/EventBadge.vue';
import { useEventBadges } from './badges/useEventBadges';
import { _ } from '@/composables/useTranslations';

const props = defineProps<{
  event: ApiResource<Event>;
}>();

const { badges } = useEventBadges(props.event);

const dimmed = computed(() => {
  const event = props.event;

  return event.type !== 'lottery' && (
    event.status === 'completed' ||
    !event.is_published ||
    !event.start_date
  );
});
</script>

<template>
  <EntityCard :resource="event" entity="event" type="index" :dimmed>
    <CardHeader
      :title="event.title"
      :kicker="event.start_date ?? _('No Date Set')"
    >
      <BadgeGroup class="mt-3">
        <EventBadge
          v-for="badge in badges"
          :key="badge.text"
          :config="badge"
        />
      </BadgeGroup>
    </CardHeader>

    <CardContent v-if="event.description">
      <p class="line-clamp-3 text-sm text-text-muted">
        {{ event.description }}
      </p>
    </CardContent>

    <CardFooter />
  </EntityCard>
</template>
