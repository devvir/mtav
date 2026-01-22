<script setup lang="ts">
import { Badge } from '@/components/badge';
import { CardContent, EntityCard } from '@/components/card';
import { fromUTC } from '@/composables/useDates';
import { _ } from '@/composables/useTranslations';

defineProps<{
  log: ApiResource<Log>;
}>();
</script>

<template>
  <EntityCard :resource="log" entity="log" type="index" class="max-w-full">
    <CardContent class="flex-row items-center justify-between gap-4">
      <span class="flex-1 truncate font-medium text-text">{{ log.event }}</span>

      <span class="shrink-0 text-sm text-text-muted">{{ log.created_by }}</span>
      <Badge v-if="log.project?.id" variant="outline" class="shrink-0">
        {{ `${_('Project')} ${log.project.id}` }}
      </Badge>
      <span class="shrink-0 text-sm text-text-subtle" :title="fromUTC(log.created_at)">
        {{ log.created_ago }}
      </span>
    </CardContent>
  </EntityCard>
</template>
