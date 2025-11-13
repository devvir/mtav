<script setup lang="ts">
import IndexPage from '@/components/entities/IndexPage.vue';

defineProps<{
  admins: ApiResources<Admin>;
  q: string;
}>();
</script>

<template>
  <IndexPage entity="admin" :resources="admins" :q />
</template>
