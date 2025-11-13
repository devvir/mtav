<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <ShowPage :entity="'family'" :resource="family" />
</template>
