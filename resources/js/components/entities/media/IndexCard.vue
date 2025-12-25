<script setup lang="ts">
import VisualIndexCard from './VisualIndexCard.vue';
import DocumentIndexCard from './DocumentIndexCard.vue';

const props = defineProps<{
  media: ApiResource<Media>;
}>();

const isDocument = computed(() => props.media.category === 'document');
</script>

<template>
  <DocumentIndexCard v-if="isDocument" :document="media" />
  <VisualIndexCard v-else :media />
</template>
