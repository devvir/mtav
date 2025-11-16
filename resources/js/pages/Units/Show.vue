<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';

defineProps<{
  unit: ApiResource<Unit>;
}>();
</script>

<template>
  <ShowPage entity="unit" :resource="unit" />
</template>
