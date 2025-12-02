<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';

defineProps<{
  logs?: ApiResources<Log>;
}>();
</script>

<template>
  <IndexPage entity="log" :resources="logs" card-size="full" />
</template>
