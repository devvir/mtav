<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';

defineProps<{
  event: ApiResource<Event>;
}>();
</script>

<template>
  <ShowPage entity="event" :resource="event" />
</template>
