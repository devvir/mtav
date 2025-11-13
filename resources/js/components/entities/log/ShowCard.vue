<script setup lang="ts">
import { Badge } from '@/components/badge';
import { Card, CardHeader, CardContent, CardFooter } from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  log: ApiResource<Log>;
}>();
</script>

<template>
  <Card :resource="log" entity="log" type="show">
    <CardHeader :title="log.event" :kicker="log.creator" />

    <CardContent>
      <div class="space-y-2">
        <p class="text-sm">
          <span class="font-medium">{{ _('Action') }}:</span> {{ log.event }}
        </p>

        <div class="flex flex-wrap gap-2">
          <Badge v-if="log.user?.id" variant="outline">User {{ log.user.id }}</Badge>
          <Badge v-if="log.project?.id" variant="outline">Project {{ log.project.id }}</Badge>
        </div>

        <p v-if="log.creator_href" class="text-xs">
          👤 <a :href="log.creator_href" class="text-primary underline">{{ log.creator }}</a>
        </p>
      </div>
    </CardContent>

    <CardFooter />
  </Card>
</template>