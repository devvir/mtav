<script setup lang="ts">
import { _ } from '@/composables/useTranslations';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center gap-3 p-3 border rounded-lg border-border">
      <!-- Thumbnail -->
      <div class="flex-shrink-0">
        <img
          :src="media.thumbnail"
          :alt="media.alt_text || media.description"
          class="w-12 h-12 object-cover rounded"
        />
      </div>

      <!-- File Info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <span>{{ media.mime_type }}</span>
          <span>•</span>
          <span>{{ Math.round(media.file_size / 1024) }} KB</span>
        </div>
      </div>
    </div>
  </div>
</template>