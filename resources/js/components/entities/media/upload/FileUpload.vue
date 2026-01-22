<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';
import { Upload } from 'lucide-vue-next';
import { getMediaConfig } from '..';
import type { MediaUploadForm } from '../types';

const files = defineModel<Record<string, File>>();

const props = defineProps<{
  form: ReturnType<typeof useForm<MediaUploadForm>>;
  category: MediaCategory;
}>();

const mediaConfig = getMediaConfig(props.category);
const dropZoneRef = ref<HTMLDivElement>();

// File Dialog
const { files: dialogFiles, open: openFileDialog } = useFileDialog({
  accept: mediaConfig.supportedTypes.join(','),
  multiple: true,
});

// Drop Zone
const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop: (files: File[] | null) => {
    if (files) {
      addFiles(files);
    }
  },
  dataTypes: mediaConfig.supportedTypes,
});

// Watch for files from dialog
watch(dialogFiles, (files: FileList | null) => {
  if (files?.length) {
    addFiles(Array.from(files));
  }
});

const getErrors = (file: File): string | void => {
  if (!mediaConfig.validationMimeTypes.includes(file.type)) {
    return _(mediaConfig.validationMessage);
  }
  if (file.size > mediaConfig.maxFileSize) {
    return _('File too large. Maximum size is 10MB.');
  }
};

const addFiles = (newFiles: File[]) => {
  for (const file of newFiles) {
    if (!getErrors(file)) {
      const key = `${file.name}-${file.size}`;
      if (files.value) {
        files.value[key] = file;
      }
    }
  }
};
</script>

<template>
  <!-- Drop Zone -->
  <div
    ref="dropZoneRef"
    :class="
      cn(
        'group relative overflow-hidden rounded-lg border-2 border-dashed p-12 text-center transition-all duration-200 focus-within:ring-2 focus-within:ring-primary focus-within:ring-offset-2 focus-within:outline-none hover:bg-muted/30',
        isOverDropZone ? 'border-primary bg-muted/50' : 'border-muted',
      )
    "
    @click="openFileDialog()"
  >
    <Upload
      class="mx-auto h-12 w-12 text-muted-foreground transition-colors group-hover:text-foreground"
    />
    <div class="mt-4 space-y-2">
      <p class="text-lg font-medium">{{ mediaConfig.dropText }}</p>
      <p class="text-sm text-muted-foreground">{{ mediaConfig.supportText }}</p>
    </div>
    <Button class="mt-6" variant="outline">{{ mediaConfig.buttonText }}</Button>
  </div>
</template>
