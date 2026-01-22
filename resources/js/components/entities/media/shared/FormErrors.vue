<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import type { MediaUploadForm } from '../types';

defineProps<{
  form: InertiaForm<MediaUploadForm>;
}>();
</script>

<template>
  <div v-if="form.hasErrors" class="space-y-1">
    <div v-for="(error, field) in form.errors" :key="field" class="text-sm text-destructive">
      {{ error }}
    </div>
  </div>
</template>
