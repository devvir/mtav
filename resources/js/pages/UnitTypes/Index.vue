<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';

defineProps<{
  unit_types: ApiResources<UnitType>;
}>();
</script>

<template>
  <IndexPage entity="unit_type" :resources="unit_types" />
</template>
