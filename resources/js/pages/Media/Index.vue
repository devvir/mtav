<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  media: ApiResources<Media>;
  q?: string;
}>();
</script>

<template>
  <IndexPage :pageTitle="_('Gallery')" entity="media" :resources="media" :q>
    <!-- Add any media-specific toolbar items here if needed -->
  </IndexPage>
</template>