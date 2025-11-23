<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Link } from '@inertiajs/vue3';
import SectionHeader from '../shared/SectionHeader.vue';

const props = defineProps<{
  media: Media[];
  totalCount: number;
}>();

const rotations = [
  { rotation: 'rotate(-6deg) translateY(8px)', zIndex: 30 },
  { rotation: 'rotate(3deg) translateY(4px)', zIndex: 20 },
  { rotation: 'rotate(0deg)', zIndex: 10 },
];

const placeholders = [
  'https://picsum.photos/400/300?1',
  'https://picsum.photos/400/300?2',
  'https://picsum.photos/400/300?3',
];

const images = computed(() => {
  const mediaUrls = props.media.slice(0, 3).map((m: Media) => m.url) || [];

  return rotations.map((config, index) => ({
    url: mediaUrls[index] || placeholders[index],
    ...config,
  }));
});
</script>

<template>
  <section>
    <SectionHeader
      :title="_('Gallery')"
      view-all-href="gallery"
      :view-all-text="totalCount > 1 ? `${_('View all')} (${totalCount})` : undefined"
    />

    <Link :href="route('gallery')" class="block">
      <div class="relative h-64 overflow-hidden rounded-lg">
        <div class="absolute inset-0 flex items-center justify-center">
          <!-- Stacked images with rotation -->
          <div class="gallery-stack relative h-48 w-64">
            <div
              v-for="(image, index) in images"
              :key="index"
              class="gallery-image absolute inset-0 cursor-pointer overflow-hidden rounded-lg border-2 border-white shadow-lg transition-all duration-300 hover:z-50 hover:scale-105 hover:rotate-0 hover:opacity-100"
              :style="{ transform: image.rotation, zIndex: image.zIndex }"
            >
              <img :src="image.url" :alt="`Gallery preview ${index + 1}`" class="size-full object-cover" />
            </div>
          </div>
        </div>
      </div>
    </Link>
  </section>
</template>

<style scoped>
.gallery-stack:hover .gallery-image:not(:hover) {
  opacity: 0.3;
}
</style>
