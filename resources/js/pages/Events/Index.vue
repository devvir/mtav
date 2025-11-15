<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';

defineProps<{
  events: ApiResources<Event>;
  q?: string;
}>();
</script>

<template>
  <IndexPage entity="event" :resources="events" :q card-size="lg" />
</template>
