<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { _ } from '@/composables/useTranslations';
import { FileText, Image, Play, X } from 'lucide-vue-next';

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
  <div class="flex items-center gap-3 rounded-lg border border-border p-3">
    <!-- Preview or Icon -->
    <div class="flex-shrink-0">
      <img
        v-if="file.type.startsWith('image/')"
        :src="createPreview(file)"
        :alt="file.name"
        class="h-12 w-12 rounded object-cover"
      />
      <div v-else class="flex h-12 w-12 items-center justify-center rounded bg-muted">
        <component :is="getFileIcon(file.type)" class="h-6 w-6" />
      </div>
    </div>

    <!-- File Info -->
    <div class="min-w-0 flex-1">
      <p class="truncate font-medium">{{ file.name }}</p>
      <div class="flex items-center gap-2 text-sm text-muted-foreground">
        <span>{{ getFileTypeLabel(file.type) }}</span>
        <span>•</span>
        <span>{{ formatFileSize(file.size) }}</span>
      </div>

      <!-- Individual Progress Bar -->
      <div v-if="processing && progress !== undefined" class="mt-2">
        <div class="h-1 w-full rounded-full bg-secondary">
          <div
            class="h-1 rounded-full bg-primary transition-all duration-300"
            :style="{ width: progress + '%' }"
          />
        </div>
      </div>
    </div>

    <!-- Remove Button -->
    <Button variant="ghost" size="sm" @click="emit('remove')" :disabled="processing">
      <X class="h-4 w-4" />
    </Button>
  </div>
</template>
