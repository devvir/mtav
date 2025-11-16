<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';

defineProps<{
  project: ApiResource<Project>;
}>();
</script>

<template>
  <ShowPage entity="project" :resource="project" modal-width="xl" />
</template>
