<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import type { InertiaForm } from '@inertiajs/vue3';
import type { MediaUploadForm } from '../types';
import { FileItem } from '.';

const files = defineModel<Record<string, File>>();

defineProps<{
  form: InertiaForm<MediaUploadForm>;
}>();
</script>

<template>
  <div v-if="Object.keys(files).length" class="space-y-3 border-t pt-6">
    <h3 class="font-medium">{{ _('Selected Files') }}</h3>
    <div class="space-y-2">
      <FileItem
        v-for="[key, file] in Object.entries(files)"
        :key="key"
        :file="file"
        :processing="form.processing"
        :progress="form.progress?.percentage"
        @remove="delete files[key]"
      />
    </div>
  </div>
</template>