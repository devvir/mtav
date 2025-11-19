<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { FileText, Image, Play, X } from 'lucide-vue-next';
import { _ } from '@/composables/useTranslations';

const emit = defineEmits<{
  remove: [];
}>();

defineProps<{
  file: File;
  processing: boolean;
  progress?: number;
}>();

const getFileIcon = (mimeType: string) => {
  if (mimeType.startsWith('image/')) return Image;
  if (mimeType.startsWith('video/')) return Play;
  if (mimeType === 'application/pdf') return FileText;
  return FileText;
};

const getFileTypeLabel = (mimeType: string): string => {
  if (mimeType.startsWith('image/')) return _('Image');
  if (mimeType.startsWith('video/')) return _('Video');
  if (mimeType === 'application/pdf') return 'PDF';
  if (mimeType.includes('word')) return 'Word';
  if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return 'Excel';
  if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return 'PowerPoint';
  if (mimeType.includes('text')) return _('Text');
  if (mimeType.includes('zip') || mimeType.includes('rar')) return _('Archive');
  return _('File');
};

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const createPreview = (file: File): string | undefined => {
  if (file.type.startsWith('image/')) {
    return URL.createObjectURL(file);
  }
  return undefined;
};
</script>

<template>
  <div class="flex items-center gap-3 p-3 border rounded-lg border-border">
    <!-- Preview or Icon -->
    <div class="flex-shrink-0">
      <img
        v-if="file.type.startsWith('image/')"
        :src="createPreview(file)"
        :alt="file.name"
        class="w-12 h-12 object-cover rounded"
      />
      <div v-else class="w-12 h-12 flex items-center justify-center bg-muted rounded">
        <component :is="getFileIcon(file.type)" class="w-6 h-6" />
      </div>
    </div>

    <!-- File Info -->
    <div class="flex-1 min-w-0">
      <p class="font-medium truncate">{{ file.name }}</p>
      <div class="flex items-center gap-2 text-sm text-muted-foreground">
        <span>{{ getFileTypeLabel(file.type) }}</span>
        <span>•</span>
        <span>{{ formatFileSize(file.size) }}</span>
      </div>

      <!-- Individual Progress Bar -->
      <div v-if="processing && progress !== undefined" class="mt-2">
        <div class="w-full bg-secondary rounded-full h-1">
          <div
            class="bg-primary h-1 rounded-full transition-all duration-300"
            :style="{ width: progress + '%' }"
          />
        </div>
      </div>
    </div>

    <!-- Remove Button -->
    <Button
      variant="ghost"
      size="sm"
      @click="emit('remove')"
      :disabled="processing"
    >
      <X class="w-4 h-4" />
    </Button>
  </div>
</template>