<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';

defineProps<{
  admin: ApiResource<Admin>;
}>();
</script>

<template>
  <ShowPage :entity="'admin'" :resource="admin" />
</template>
