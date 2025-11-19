<script setup lang="ts">
import { getMediaConfig, FormHeader } from '..';

const props = defineProps<{
  category: MediaCategory;
}>();

const mediaConfig = getMediaConfig(props.category);
</script>

<template>
  <FormHeader :title="mediaConfig.title" :subtitle="mediaConfig.subtitle" />
</template>