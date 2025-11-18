<script setup lang="ts">
import { EntityCard, CardContent } from '@/components/card';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <EntityCard :resource="media" entity="media" type="index" class="overflow-hidden">
    <CardContent class="group relative overflow-hidden p-0!">
      <!-- Fixed aspect ratio container for consistent sizing -->
      <div class="aspect-square w-full overflow-hidden">
        <!-- Thumbnail image for all media types -->
        <img
          :src="media.thumbnail"
          :alt="media.alt_text || media.description || `${media.category} media`"
          fetchpriority="high"
          class="h-full w-full object-cover transition-all duration-300 group-hocus:scale-105 group-hocus:opacity-75"
          loading="lazy"
        />

        <!-- Subtle overlay with description and owner -->
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 via-black/60 via-70% to-transparent p-3 text-white transition-all duration-300">
          <div class="transition-transform duration-300 not-group-hocus:translate-y-[calc(100%-1.75em)] not-group-hocus:truncate group-hocus:line-clamp-3">
            {{ media.description || 'No description' }}
          </div>
          <div class="mt-1 text-xs opacity-75">
            Published by {{ media.owner?.name || 'Unknown user' }}
          </div>
        </div>
      </div>
    </CardContent>
  </EntityCard>
</template>
