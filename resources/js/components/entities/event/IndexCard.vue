<script setup lang="ts">
import { Badge, BadgeGroup, BinaryBadge } from '@/components/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  event: ApiResource<Event>;
}>();
</script>

<template>
  <Card :resource="event" entity="event" type="index">
    <CardHeader :title="event.title" :kicker="_('Event')" />

    <CardContent>
      <div class="text-sm text-text-muted">{{ event.start_date }}</div>

      <BadgeGroup>
        <BinaryBadge :when="event.is_published" :then="_('Published')" :else="_('Draft')" />
        <Badge variant="outline">{{ event.type_label }}</Badge>
      </BadgeGroup>

      <div v-if="event.location" class="text-sm text-text-muted">
        📍 {{ event.location }}
      </div>
    </CardContent>

    <CardFooter />
  </Card>
</template>
