<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { ChevronLeft, ChevronRight, LoaderCircle } from 'lucide-vue-next';

const carouselImages = [
  '/images/intro/1.jpg',
  '/images/intro/2.jpg',
  '/images/intro/3.jpg',
  '/images/intro/4.jpg',
  '/images/intro/5.jpg',
];

const currentSlide = ref(0);
const carouselInterval = ref<NodeJS.Timeout | null>(null);
const imagesLoaded = ref(false);
const loadedCount = ref(0);

const nextSlide = () => (currentSlide.value = (currentSlide.value + 1) % carouselImages.length);
const prevSlide = () =>
  (currentSlide.value = currentSlide.value ? currentSlide.value - 1 : carouselImages.length - 1);
const goToSlide = (index: number) => (currentSlide.value = index);

const startAutoplay = () => {
  if (imagesLoaded.value) {
    carouselInterval.value = setInterval(nextSlide, 5000);
  }
};

const stopAutoplay = () => {
  if (carouselInterval.value) {
    clearInterval(carouselInterval.value);
    carouselInterval.value = null;
  }
};

const preloadImages = () => {
  loadedCount.value = 0;

  carouselImages.forEach((src) => {
    const img = new Image();

    img.onload = () => {
      loadedCount.value++;
      if (loadedCount.value === carouselImages.length) {
        imagesLoaded.value = true;
        startAutoplay();
      }
    };

    img.onerror = () => {
      loadedCount.value++;
      if (loadedCount.value === carouselImages.length) {
        imagesLoaded.value = true;
        startAutoplay();
      }
    };

    img.src = src;
  });
};

onMounted(() => preloadImages());
onUnmounted(() => stopAutoplay());
</script>

<template>
  <div
    class="group relative overflow-hidden rounded-lg border border-primary/20 bg-muted/50 shadow-lg"
    @mouseenter="stopAutoplay"
    @mouseleave="startAutoplay"
  >
    <!-- Carousel images -->
    <div class="relative aspect-video">
      <!-- Loading skeleton -->
      <div v-if="!imagesLoaded" class="absolute inset-0 flex items-center justify-center bg-muted">
        <LoaderCircle class="h-8 w-8 animate-spin text-muted-foreground" />
      </div>

      <TransitionGroup name="carousel">
        <img
          v-for="(image, index) in carouselImages"
          v-show="currentSlide === index && imagesLoaded"
          :key="image"
          :src="image"
          :alt="`${_('Collaborative housing community')} ${index + 1}`"
          :loading="index === 0 ? 'eager' : 'lazy'"
          class="absolute inset-0 h-full w-full object-cover"
        />
      </TransitionGroup>

      <!-- Navigation buttons -->
      <button
        @click="prevSlide"
        class="absolute top-1/2 left-2 -translate-y-1/2 rounded-full bg-black/50 p-1.5 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-black/70 sm:p-2"
        :aria-label="_('Previous image')"
      >
        <ChevronLeft class="h-4 w-4 sm:h-5 sm:w-5" />
      </button>

      <button
        @click="nextSlide"
        class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-black/50 p-1.5 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-black/70 sm:p-2"
        :aria-label="_('Next image')"
      >
        <ChevronRight class="h-4 w-4 sm:h-5 sm:w-5" />
      </button>
    </div>

    <!-- Dot indicators -->
    <div class="absolute bottom-2 left-1/2 flex -translate-x-1/2 gap-1.5 sm:bottom-3 sm:gap-2">
      <button
        v-for="(_image, index) in carouselImages"
        :key="`dot-${index}`"
        @click="goToSlide(index)"
        class="h-1.5 w-1.5 rounded-full transition-all sm:h-2 sm:w-2"
        :class="currentSlide === index ? 'w-4 bg-white sm:w-6' : 'bg-white/50 hover:bg-white/75'"
        :aria-label="`${_('Go to image')} ${index + 1}`"
      />
    </div>
  </div>
</template>

<style scoped>
.carousel-enter-active,
.carousel-leave-active {
  transition: opacity 0.5s ease;
}

.carousel-enter-from {
  opacity: 0;
}

.carousel-leave-to {
  opacity: 0;
}
</style>
