<script setup lang="ts">
import { _ } from '@/composables/useTranslations';

defineProps<{
  media: ApiResource<Media>;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const isPlaying = ref(false);
const showControls = ref(false);

const togglePlay = () => {
  if (!videoRef.value) return;

  if (videoRef.value.paused) {
    videoRef.value.play();
    isPlaying.value = true;
  } else {
    videoRef.value.pause();
    isPlaying.value = false;
  }
};

const onVideoEnd = () => {
  isPlaying.value = false;
};

const onMouseEnter = () => {
  showControls.value = true;
};

const onMouseLeave = () => {
  if (!videoRef.value?.paused) {
    showControls.value = false;
  }
};
</script>

<template>
  <div class="relative mx-auto flex max-h-[85vh] max-w-[95vw] items-center justify-center">
    <div class="relative group" @mouseenter="onMouseEnter" @mouseleave="onMouseLeave">
      <!-- Video Element -->
      <video ref="videoRef" :src="media.url" class="block max-h-[85vh] max-w-full h-auto w-auto object-contain" @click="togglePlay" @ended="onVideoEnd"
        controls preload="metadata" playsinline>
        {{ _('Your browser does not support the video tag.') }}
      </video>

      <!-- Play/Pause Overlay (shown when video is paused) -->
      <div v-show="!isPlaying"
        class="absolute inset-0 flex items-center justify-center cursor-pointer bg-black/10 transition-opacity"
        @click="togglePlay">
        <div class="rounded-full bg-black/50 p-6 transition-all duration-300 hover:bg-black/70 hover:scale-110">
          <svg class="h-12 w-12 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>
