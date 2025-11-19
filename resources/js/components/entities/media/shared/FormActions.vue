<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { InertiaForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { MediaUploadForm } from '../types';

defineEmits<{
  cancel: [];
  submit: [];
}>();

const props = defineProps<{
  form: InertiaForm<MediaUploadForm>;
  submitText?: string;
}>();

const canUpload = computed(() => {
  // For upload: check if files exist
  if ('files' in props.form && typeof props.form.files === 'object') {
    return Object.keys(props.form.files).length && !props.form.processing;
  }
  // For edit: just check not processing
  return !props.form.processing;
});
</script>

<template>
  <div class="flex items-center justify-between border-t pt-6">
    <Button
      type="button"
      variant="ghost"
      :disabled="form.processing"
      @click="$emit('cancel')"
    >
      {{ _('Cancel') }}
    </Button>

    <Button
      type="button"
      :disabled="!canUpload"
      @click="$emit('submit')"
    >
      <span v-if="form.processing">{{ _('Processing...') }}</span>
      <span v-else>{{ submitText || _('Upload Files') }}</span>
    </Button>
  </div>
</template>