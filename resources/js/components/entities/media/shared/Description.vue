<script setup lang="ts">
import { cn } from '@/lib/utils';
import { _ } from '@/composables/useTranslations';
import { AlertCircle } from 'lucide-vue-next';
import { Label } from '@/components/ui/label';
import type { InertiaForm } from '@inertiajs/vue3';
import type { MediaUploadForm } from '../types';

const description = defineModel<string>();

defineProps<{
  form: InertiaForm<MediaUploadForm>;
  action: 'upload' | 'edit';
}>();
</script>

<template>
  <div class="space-y-3">
    <Label for="description">{{ _('Description') }}</Label>

    <textarea
      id="description"
      v-model="description"
      :placeholder="_('Describe what you\'re sharing...')"
      rows="3"
      :class="cn(
        'min-h-[80px] w-full resize-none rounded-md border px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        form.errors.description
          ? 'border-red-300 focus-visible:ring-red-300 dark:border-red-600/50 dark:focus-visible:ring-red-500/50'
          : 'border-input bg-background focus-visible:ring-ring'
      )"
      :disabled="form.processing"
    />

    <div v-if="form.errors.description" class="flex items-center gap-1 text-xs text-red-500 dark:text-red-400/80">
      <AlertCircle class="size-3" />
      {{ form.errors.description }}
    </div>
    <p v-else-if="action !== 'edit'" class="text-xs text-muted-foreground">
      {{ _('Please add a description to your content') }}
    </p>
  </div>
</template>