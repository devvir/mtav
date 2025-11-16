<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';

defineProps<{
  units: ApiResources<Unit>;
}>();
</script>

<template>
  <IndexPage entity="unit" :resources="units" />
</template>
