<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

defineProps<{
  title?: string;
  description?: string;
  wide?: boolean;
}>();
</script>

<template>
  <AuthLayout :title :description :wide>
    <slot />
  </AuthLayout>
</template>
