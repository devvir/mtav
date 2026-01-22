<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import type { InertiaForm } from '@inertiajs/vue3';
import type { MediaUploadForm } from '../types';

defineProps<{
  form: InertiaForm<MediaUploadForm>;
}>();
</script>

<template>
  <div v-if="form.progress" class="space-y-2">
    <div class="flex justify-between text-sm">
      <span>{{ _('Uploading...') }}</span>
      <span>{{ Math.round(form.progress.percentage) }}%</span>
    </div>

    <div class="h-2 w-full rounded-full bg-secondary">
      <div
        class="h-2 rounded-full bg-primary transition-all duration-300"
        :style="{ width: form.progress.percentage + '%' }"
      />
    </div>
  </div>
</template>
