<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';

defineProps<{
  notifications?: ApiResources<Notification>;
}>();
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <IndexPage entity="notification" :resources="notifications" card-size="full" />
  </div>
</template>
