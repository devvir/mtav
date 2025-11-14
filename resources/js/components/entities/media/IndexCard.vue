<script setup lang="ts">
import { Badge } from '@/components/badge';
import { Card, CardContent, CardHeader } from '@/components/card';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <Card :resource="media" entity="media" type="index">
    <!-- Show image preview for images -->
    <div v-if="media.category === 'image'" class="relative aspect-video overflow-hidden">
      <img
        :src="media.url"
        :alt="media.alt_text || media.description || 'Media image'"
        class="h-full w-full object-cover"
        loading="lazy"
      />
    </div>

    <!-- Show video thumbnail for videos -->
    <div v-else-if="media.category === 'video'" class="relative aspect-video overflow-hidden bg-gray-100">
      <video
        :src="media.url"
        class="h-full w-full object-cover"
        preload="metadata"
        muted
      >
        Video not supported
      </video>
      <div class="absolute inset-0 flex items-center justify-center bg-black/20">
        <svg class="h-12 w-12 text-white" fill="currentColor" viewBox="0 0 24 24">
          <path d="M8 5v14l11-7z"/>
        </svg>
      </div>
    </div>

    <!-- Fallback for other file types -->
    <div v-else class="flex aspect-video items-center justify-center bg-gray-100">
      <div class="text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="mt-2 text-sm font-medium text-gray-500">{{ media.category.toUpperCase() }}</p>
      </div>
    </div>

    <CardHeader :title="media.path.split('/').pop()" :subtitle="media.description" />

    <CardContent>
      <div class="flex items-center gap-2">
        <Badge variant="outline">{{ media.category }}</Badge>
        <span v-if="media.dimensions" class="text-xs text-muted-foreground">{{ media.dimensions }}</span>
        <span class="text-xs text-muted-foreground">{{ media.path.split('.').pop()?.toUpperCase() }}</span>
      </div>
    </CardContent>
  </Card>
</template>
