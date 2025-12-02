<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';

defineProps<{
  unit_type: ApiResource<UnitType>;
}>();
</script>

<template>
  <ShowPage entity="unit_type" :resource="unit_type" />
</template>
