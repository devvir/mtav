<script setup lang="ts">
import { EntityCard, CardActions, CardContent, CardHeader } from '@/components/card';
import { _ } from '@/composables/useTranslations';
import { VideoIcon } from 'lucide-vue-next';
import ImageDisplay from './image/ImageDisplay.vue';
import VideoPlayer from './video/VideoPlayer.vue';
import MediaDescription from './shared/MediaDescription.vue';
import MediaFallback from './shared/MediaFallback.vue';

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
    class="group overflow-hidden ring-2 ring-card-elevated-foreground/50 min-h-32"
  >
    <!-- CardHeader for videos with actions and metadata -->
    <CardHeader
      v-if="media.category === 'video'"
      :resource="media"
      entity="media"
      type="show"
      :title="media.description || _('No description available')"
      :kicker="media.created_ago"
      class="p-base align-center"
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
      class="not-group-hover:not-group-active:not-group-focus:hidden absolute left-3 top-3 z-10 rounded-lg [&>*]:bg-black/30 backdrop-blur-sm"
    />

    <CardContent
      :style="[
        `--m-ratio: min(calc(90vh/${media.height}),calc(90vw/${media.width}))`,
        `--m-width: calc(var(--m-ratio) * ${media.width})`,
        `--m-height: calc(var(--m-ratio) * ${media.height})`,
      ]"
      class="relative p-0! flex-1"
      :class="media.width && 'inertia-ui-sucks w-[var(--m-width)] h-[var(--m-height)] min-w-full'"
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
