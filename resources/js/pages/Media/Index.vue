<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  media: ApiResources<Media>;
  category: string;
}>();
</script>

<template>
  <IndexPage
    :pageTitle="category === 'visual' ? _('Gallery') : _('Documents')"
    entity="media"
    :resources="media"
    :cardSize="category === 'document' ? 'lg' : 'xl'"
  />
</template>