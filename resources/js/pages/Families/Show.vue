<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  family: ApiResource<Family>;
}>();
</script>

<template>
  <ShowPage :entity="'family'" :resource="family" />
</template>
