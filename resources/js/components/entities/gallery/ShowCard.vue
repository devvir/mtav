<script setup lang="ts">
import { Badge } from '@/components/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/card';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <Card :resource="media" entity="gallery" type="show">
    <CardHeader :title="media.filename" :subtitle="media.description" />

    <CardContent>
      <div class="space-y-2">
        <div
          class="flex h-20 w-full items-center justify-center rounded bg-gray-200 text-sm text-gray-500"
        >
          📷 Image Preview
        </div>

        <p class="line-clamp-2 text-sm">{{ media.description }}</p>

        <div class="flex flex-wrap gap-2">
          <Badge variant="outline">{{ media.filename.split('.').pop()?.toUpperCase() }}</Badge>
          <Badge variant="outline">Image</Badge>
        </div>
      </div>
    </CardContent>

    <CardFooter />
  </Card>
</template>
