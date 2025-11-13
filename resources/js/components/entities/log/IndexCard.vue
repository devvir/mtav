<script setup lang="ts">
import { Badge } from '@/components/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  log: ApiResource<Log>;
}>();
</script>

<template>
  <Card :resource="log" entity="log" type="index">
    <CardHeader :title="log.event" :kicker="log.creator" />

    <CardContent>
      <Badge v-if="log.project?.id" variant="outline">
        {{ `${_('Project')} ${log.project.id}` }}
      </Badge>
    </CardContent>

    <CardFooter />
  </Card>
</template>