<script setup lang="ts">
import { CardActions, CardContent, CardHeader, EntityCard } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { VideoIcon } from 'lucide-vue-next';
import ImageDisplay from './image/ImageDisplay.vue';
import MediaDescription from './shared/MediaDescription.vue';
import MediaFallback from './shared/MediaFallback.vue';
import VideoPlayer from './video/VideoPlayer.vue';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <EntityCard
    :resource="media"
    entity="media"
    type="show"
    tabindex="1"
    class="group min-h-32 overflow-hidden ring-2 ring-card-elevated-foreground/50"
  >
    <!-- CardHeader for videos with actions and metadata -->
    <CardHeader
      v-if="media.category === 'video'"
      :resource="media"
      entity="media"
      type="show"
      :title="media.description || _('No description available')"
      :kicker="media.created_ago"
      class="align-center p-base"
    >
      <template #icon>
        <VideoIcon class="size-12" />
      </template>
      {{ `${_('Published by')} ${media.owner?.name || _('Unknown user')}` }}
    </CardHeader>

    <!-- CardActions positioned in top-left corner for images (with background for visibility) -->
    <CardActions
      v-if="media.category !== 'video'"
      type="full"
      class="absolute top-3 left-3 z-10 rounded-lg backdrop-blur-sm not-group-hover:not-group-active:not-group-focus:hidden [&>*]:bg-black/30"
    />

    <CardContent
      :style="[
        `--m-ratio: min(calc(90vh/${media.height}),calc(90vw/${media.width}))`,
        `--m-width: calc(var(--m-ratio) * ${media.width})`,
        `--m-height: calc(var(--m-ratio) * ${media.height})`,
      ]"
      class="relative flex-1 p-0!"
      :class="media.width && 'inertia-ui-sucks h-[var(--m-height)] w-[var(--m-width)] min-w-full'"
    >
      <!-- Image Display -->
      <ImageDisplay v-if="media.category === 'image'" :media class="h-full" />

      <!-- Video Player -->
      <VideoPlayer v-else-if="media.category === 'video'" :media class="h-full" />

      <!-- Fallback for other media types (audio, documents) -->
      <MediaFallback v-else :media />

      <!-- Description overlay (only for non-video media) -->
      <MediaDescription v-if="media.category !== 'video'" :media />
    </CardContent>
  </EntityCard>
</template>
