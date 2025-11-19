<script setup lang="ts">
import { EntityCard, CardActions, CardContent } from '@/components/card';
import { _ } from '@/composables/useTranslations';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <EntityCard :resource="media" entity="media" type="show" tabindex="1"
    class="group overflow-hidden ring-2 ring-card-elevated-foreground/50">
    <!-- CardActions positioned in top-left corner with background for visibility -->
    <CardActions type="full" class="not-group-hover:not-group-active:not-group-focus:hidden absolute left-3 top-3 z-10 rounded-lg [&>*]:bg-black/30 backdrop-blur-sm p-0!" />

    <CardContent class="relative overflow-hidden p-0!">
      <!-- Dynamic container that respects original aspect ratio but uses more modal space -->
      <div class="relative mx-auto h-auto w-full max-w-[95vw] min-w-0">
        <!-- Image for image media -->
        <img v-if="media.category === 'image'" :src="media.url"
          :alt="media.alt_text || media.description || 'Media image'" fetchpriority="high"
          class="h-auto w-full object-cover" loading="lazy" />

        <!-- Video with original aspect ratio -->
        <div v-else-if="media.category === 'video'" class="relative">
          <img :src="media.url" :alt="media.description || 'Video thumbnail'" class="h-auto w-full object-cover"
            loading="lazy" />
          <!-- Play button overlay -->
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="rounded-full bg-black/50 p-6 transition-all duration-300 hover:bg-black/70 hover:scale-110">
              <svg class="h-12 w-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Fallback for other media types -->
        <div v-else class="aspect-video flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
          <div class="text-center opacity-60">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
              stroke-width="1">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-4 text-lg font-medium text-gray-500">{{ media.category.toUpperCase() }}</p>
            <p class="text-sm text-gray-400">{{ media.path.split('.').pop()?.toUpperCase() }}</p>
          </div>
        </div>

        <!-- Description overlay - similar to IndexCard but adapted for show view -->
        <div
          class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/85 via-black/60 via-60% to-transparent p-4 text-white">
          <div class="space-y-2">
            <p class="text-lg leading-relaxed">
              {{ media.description || 'No description available' }}
            </p>
            <div class="flex items-center justify-between text-sm opacity-90">
              <span>{{ _('Published by') }} {{ media.owner?.name || _('Unknown user') }}</span>
              <span :title="media.created_at">{{ media.created_ago }}</span>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </EntityCard>
</template>
