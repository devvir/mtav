<script setup lang="ts">
import { Card, CardHeader, CardContent } from '@/components/card';
import { Badge } from '@/components/badge';

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