<script setup lang="ts">
defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <div class="relative mx-auto w-auto">
    <img
      :src="media.url"
      :alt="media.alt_text || media.description || 'Media image'"
      fetchpriority="high"
      class="h-full w-full object-contain"
      loading="lazy"
    />
  </div>
</template>
