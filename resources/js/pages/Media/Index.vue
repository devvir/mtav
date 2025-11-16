<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  media: ApiResources<Media>;
}>();
</script>

<template>
  <IndexPage :pageTitle="_('Gallery')" entity="media" :resources="media" cardSize="xl" />
</template>