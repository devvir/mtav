<script setup lang="ts">
import { Badge } from '@/components/badge';
import { Card, CardContent, CardHeader } from '@/components/card';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <Card :resource="media" entity="gallery" type="index">
    <CardHeader :title="media.filename" :subtitle="media.description" />

    <CardContent>
      <div class="flex items-center gap-2">
        <Badge variant="outline">Image</Badge>
        <span class="text-xs">{{ media.filename.split('.').pop()?.toUpperCase() }}</span>
      </div>
    </CardContent>
  </Card>
</template>
