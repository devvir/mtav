<script setup lang="ts">
defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <div class="space-y-3">
    <div class="flex items-center gap-3 rounded-lg border border-border p-3">
      <!-- Thumbnail -->
      <div class="shrink-0">
        <img
          :src="media.thumbnail"
          :alt="media.alt_text || media.description"
          class="h-12 w-12 rounded object-cover"
        />
      </div>

      <!-- File Info -->
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <span>{{ media.mime_type }}</span>
          <span>•</span>
          <span>{{ Math.round(media.file_size / 1024) }} KB</span>
        </div>
      </div>
    </div>
  </div>
</template>
