<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';

defineProps<{
  log: ApiResource<Log>;
}>();
</script>

<template>
  <ShowPage entity="log" :resource="log" />
</template>
