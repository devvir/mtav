<script setup lang="ts">
import { Card, CardContent, CardFooter, CardHeader } from '@/components/card';
import { Badge } from '@/components/badge';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <Card :resource="media" entity="gallery" type="show">
    <CardHeader :title="media.filename" :subtitle="media.description" />

    <CardContent>
      <div class="space-y-2">
        <div class="w-full h-20 bg-gray-200 rounded flex items-center justify-center text-gray-500 text-sm">
          📷 Image Preview
        </div>

        <p class="text-sm line-clamp-2">{{ media.description }}</p>

        <div class="flex flex-wrap gap-2">
          <Badge variant="outline">{{ media.filename.split('.').pop()?.toUpperCase() }}</Badge>
          <Badge variant="outline">Image</Badge>
        </div>
      </div>
    </CardContent>

    <CardFooter />
  </Card>
</template>