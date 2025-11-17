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
        <!-- Image for image media -->
        <img
          v-if="media.category === 'image'"
          :src="media.url"
          :alt="media.alt_text || media.description || 'Media image'"
          fetchpriority="high"
          class="h-full w-full object-cover transition-all duration-300 group-hocus:scale-105 group-hocus:opacity-75"
          loading="lazy"
        />

        <!-- Video thumbnail with play overlay -->
        <div v-else-if="media.category === 'video'" class="relative h-full w-full">
          <img
            :src="media.url"
            :alt="media.description || 'Video thumbnail'"
            class="h-full w-full object-cover transition-all duration-300 group-hocus:scale-105 group-hocus:opacity-75"
            loading="lazy"
          />
          <!-- Play button overlay -->
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="rounded-full bg-black/50 p-4 transition-all duration-300 group-hocus:bg-black/70 group-hocus:scale-110">
              <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Fallback for other media types -->
        <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
          <div class="text-center opacity-60 transition-opacity duration-300 group-hocus:opacity-80">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-3 text-sm font-medium text-gray-500">{{ media.category.toUpperCase() }}</p>
            <p class="text-xs text-gray-400">{{ media.path.split('.').pop()?.toUpperCase() }}</p>
          </div>
        </div>

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
