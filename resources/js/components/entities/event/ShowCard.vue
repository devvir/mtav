<script setup lang="ts">
import { Badge, BinaryBadge } from '@/components/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  event: ApiResource<Event>;
}>();
</script>

<template>
  <Card :resource="event" entity="event" type="show">
    <CardHeader :title="event.title" :kicker="_('Event')" />

    <CardContent>
      <!-- Event Details -->
      <div class="space-y-2 text-sm">
        <div>
          <strong>{{ _('Start Date') }}:</strong> {{ event.start_date }}
        </div>
        <div v-if="event.end_date">
          <strong>{{ _('Ends') }}:</strong> {{ event.end_date }}
        </div>
        <div v-if="event.location">
          <strong>{{ _('Location') }}:</strong> {{ event.location }}
        </div>
      </div>

      <!-- Status & Type -->
      <div class="flex flex-wrap gap-2">
        <BinaryBadge :when="event.is_published" :then="_('Published')" :else="_('Draft')" />
        <Badge variant="outline">{{ event.type_label }}</Badge>
        <BinaryBadge :when="event.is_lottery" :then="_('Lottery')" variant="danger" />
      </div>

      <!-- Description -->
      <div class="rounded-lg border border-border bg-surface-elevated p-3">
        <h4 class="mb-2 text-sm font-medium text-text">{{ _('Description') }}</h4>
        <p class="line-clamp-3 text-sm text-text-muted">{{ event.description }}</p>
      </div>
    </CardContent>

    <CardFooter />
  </Card>
</template>
