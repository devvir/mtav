<script setup lang="ts">
defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <div class="relative w-auto mx-auto">
    <img
      :src="media.url"
      :alt="media.alt_text || media.description || 'Media image'"
      fetchpriority="high"
      class="object-contain w-full h-full"
      loading="lazy"
    />
  </div>
</template>
