<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';

defineProps<{
  events: ApiResources<Event>;
}>();
</script>

<template>
  <IndexPage entity="event" :resources="events" card-size="lg" />
</template>
