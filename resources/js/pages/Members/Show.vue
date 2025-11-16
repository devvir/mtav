<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';

defineProps<{
  member: ApiResource<Member>;
}>();
</script>

<template>
  <ShowPage entity="member" :resource="member" />
</template>
