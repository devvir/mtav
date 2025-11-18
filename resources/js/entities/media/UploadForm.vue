<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { AlertCircle, FileText, Image, Play, Upload, X } from 'lucide-vue-next';
import { _ } from '@/composables/useTranslations';
import { cn } from '@/lib/utils';

interface UploadFile {
  id: string;
  file: File;
  error?: string;
  preview?: string;
  status: 'pending' | 'uploading' | 'completed' | 'failed';
}

interface Props {
  supportedTypes: string[];
  validationMimeTypes: string[];
  maxFileSize?: number;
  title: string;
  subtitle: string;
  dropText: string;
  supportText: string;
  buttonText: string;
  submitText: string;
  submittingText: string;
  validationMessage: string;
}

const props = withDefaults(defineProps<Props>(), {
  maxFileSize: 10 * 1024 * 1024, // 10MB
});

const uploadFiles = ref<UploadFile[]>([]);
const description = ref('');
const isUploading = ref(false);
const dropZoneRef = ref<HTMLDivElement>();

// File Dialog
const { files: dialogFiles, open: openFileDialog } = useFileDialog({
  accept: props.supportedTypes.join(','),
  multiple: true,
});

// Drop Zone
const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop: (files: File[] | null) => {
    if (files) {
      addFiles(files);
    }
  },
  dataTypes: props.supportedTypes,
});

// Watch for files from dialog
watch(dialogFiles, (files: FileList | null) => {
  if (files?.length) {
    addFiles(Array.from(files));
  }
});

const validateFile = (file: File): string | null => {
  if (!props.validationMimeTypes.includes(file.type)) {
    return _(props.validationMessage);
  }
  if (file.size > props.maxFileSize) {
    return _('File too large. Maximum size is 10MB.');
  }
  return null;
};

const createPreview = async (file: File): Promise<string | undefined> => {
  if (file.type.startsWith('image/')) {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onload = (e) => resolve(e.target?.result as string);
      reader.readAsDataURL(file);
    });
  }
  return undefined;
};

const addFiles = async (files: File[]) => {
  for (const file of files) {
    // Check if file already added
    if (uploadFiles.value.some((uf: UploadFile) => uf.file.name === file.name && uf.file.size === file.size)) {
      continue;
    }

    const error = validateFile(file);
    const preview = await createPreview(file);

    const uploadFile: UploadFile = {
      id: Math.random().toString(36).substr(2, 9),
      file,
      error: error || undefined,
      preview,
      status: error ? 'failed' : 'pending',
    };

    uploadFiles.value.push(uploadFile);
  }
};

const removeFile = (id: string) => {
  const index = uploadFiles.value.findIndex((f: UploadFile) => f.id === id);
  if (index !== -1) {
    uploadFiles.value.splice(index, 1);
  }
};

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

const canUpload = computed(() =>
  uploadFiles.value.some((f: UploadFile) => f.status === 'pending') && !isUploading.value
);

const completedCount = computed(() =>
  uploadFiles.value.filter((f: UploadFile) => f.status === 'completed').length
);

const overallProgress = computed(() =>
  uploadFiles.value.length > 0 ? Math.round((completedCount.value / uploadFiles.value.length) * 100) : 0
);

defineEmits<{
  submit: [files: UploadFile[], description: string];
  cancel: [];
}>();

defineExpose({
  uploadFiles,
  description,
  isUploading,
  canUpload,
  setUploading: (value: boolean) => { isUploading.value = value; },
  setFileStatus: (id: string, status: UploadFile['status'], error?: string) => {
    const file = uploadFiles.value.find(f => f.id === id);
    if (file) {
      file.status = status;
      if (error) file.error = error;
    }
  },
  setAllFilesStatus: (status: UploadFile['status'], error?: string) => {
    uploadFiles.value.forEach(file => {
      file.status = status;
      if (error) file.error = error;
    });
  },
});
</script>

<template>
  <div class="mx-auto w-full max-w-2xl space-y-6 p-6">
    <div class="space-y-2">
      <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
      <p class="text-sm text-text-muted">{{ subtitle }}</p>
    </div>

    <!-- Upload Area -->
    <div
      ref="dropZoneRef"
      :class="cn(
        'relative rounded-lg border-2 border-dashed p-8 text-center transition-colors cursor-pointer',
        isOverDropZone
          ? 'border-primary bg-primary/5'
          : 'border-border hover:border-border-interactive'
      )"
      @click="openFileDialog()"
    >
      <div class="space-y-4">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-surface-elevated">
          <Upload class="size-6 text-text-muted" />
        </div>

        <div class="space-y-2">
          <p class="text-sm font-medium">{{ dropText }}</p>
          <p class="text-xs text-text-muted">{{ supportText }}</p>
        </div>

        <Button
          type="button"
          variant="outline"
          @click.stop="openFileDialog()"
        >
          {{ buttonText }}
        </Button>
      </div>
    </div>

    <!-- Description Input -->
    <div class="space-y-2">
      <Label for="description">{{ _('Description') }}</Label>
      <textarea
        id="description"
        v-model="description"
        :placeholder="_('Describe what you\'re sharing...')"
        rows="3"
        :class="cn(
          'min-h-[80px] w-full resize-none rounded-md border px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
          $page.props.errors?.description
            ? 'border-red-300 focus-visible:ring-red-300 dark:border-red-600/50 dark:focus-visible:ring-red-500/50'
            : 'border-input bg-background focus-visible:ring-ring'
        )"
      />

      <!-- Validation Error for Description -->
      <div
        v-if="$page.props.errors?.description"
        class="flex items-center gap-1 text-xs text-red-500 dark:text-red-400/80"
      >
        <AlertCircle class="size-3" />
        {{ $page.props.errors.description }}
      </div>

      <p v-else class="text-xs text-text-muted">
        {{ _('Add a description to help others understand your upload') }}
      </p>
    </div>

    <!-- File List -->
    <div v-if="uploadFiles.length > 0" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-medium">{{ _('Files to Upload') }}</h3>
        <span class="text-sm text-text-muted">
          {{ uploadFiles.length }} {{ uploadFiles.length === 1 ? _('File') : _('Files') }}
        </span>
      </div>

      <!-- Overall Progress (when uploading) -->
      <div v-if="isUploading" class="space-y-2">
        <div class="flex items-center justify-between text-sm">
          <span>{{ _('Uploading files...') }}</span>
          <span>{{ overallProgress }}%</span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-secondary">
          <div
            class="h-full bg-primary transition-all duration-500 ease-out"
            :style="{ width: `${overallProgress}%` }"
          />
        </div>
      </div>

      <div class="space-y-3">
        <div
          v-for="uploadFile in uploadFiles"
          :key="uploadFile.id"
          class="flex items-center gap-3 rounded-lg border p-3"
        >
          <!-- Preview/Icon -->
          <div class="flex-shrink-0">
            <div
              v-if="uploadFile.preview"
              class="relative size-12 overflow-hidden rounded border bg-surface-elevated"
            >
              <img
                :src="uploadFile.preview"
                :alt="uploadFile.file.name"
                class="size-full object-cover"
              />
            </div>
            <div
              v-else
              class="flex size-12 items-center justify-center rounded border bg-surface-elevated"
              :class="{
                'bg-blue-50 border-blue-200': uploadFile.file.type === 'application/pdf',
                'bg-green-50 border-green-200': uploadFile.file.type.startsWith('video/'),
                'bg-purple-50 border-purple-200': uploadFile.file.type.startsWith('image/'),
                'bg-orange-50 border-orange-200': uploadFile.file.type.includes('word') || uploadFile.file.type.includes('excel') || uploadFile.file.type.includes('powerpoint'),
              }"
            >
              <component
                :is="getFileIcon(uploadFile.file.type)"
                class="size-6"
                :class="{
                  'text-blue-600': uploadFile.file.type === 'application/pdf',
                  'text-green-600': uploadFile.file.type.startsWith('video/'),
                  'text-purple-600': uploadFile.file.type.startsWith('image/'),
                  'text-orange-600': uploadFile.file.type.includes('word') || uploadFile.file.type.includes('excel') || uploadFile.file.type.includes('powerpoint'),
                  'text-text-muted': !uploadFile.file.type.startsWith('video/') && !uploadFile.file.type.startsWith('image/') && uploadFile.file.type !== 'application/pdf' && !uploadFile.file.type.includes('word') && !uploadFile.file.type.includes('excel') && !uploadFile.file.type.includes('powerpoint')
                }"
              />
            </div>
          </div>

          <!-- File Info -->
          <div class="min-w-0 flex-1">
            <div class="truncate font-medium text-sm">{{ uploadFile.file.name }}</div>
            <div class="flex items-center gap-2 text-xs text-text-muted">
              <span>{{ formatFileSize(uploadFile.file.size) }}</span>
              <span>• {{ getFileTypeLabel(uploadFile.file.type) }}</span>
              <span
                v-if="uploadFile.status === 'uploading'"
                class="text-primary"
              >
                • {{ _('Uploading...') }}
              </span>
              <span
                v-else-if="uploadFile.status === 'completed'"
                class="text-success"
              >
                • {{ _('Completed') }}
              </span>
              <span
                v-else-if="uploadFile.status === 'failed'"
                class="text-red-600 dark:text-red-400"
              >
                • {{ _('Failed') }}
              </span>
            </div>

            <!-- Error Message -->
            <div
              v-if="uploadFile.error"
              class="flex items-center gap-1 text-xs text-red-600 dark:text-red-400 mt-1"
            >
              <AlertCircle class="size-3" />
              {{ uploadFile.error }}
            </div>
          </div>

          <!-- Remove Button -->
          <Button
            v-if="uploadFile.status !== 'uploading'"
            type="button"
            variant="ghost"
            size="sm"
            @click="removeFile(uploadFile.id)"
          >
            <X class="size-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between border-t pt-6">
      <Button
        type="button"
        variant="ghost"
        @click="$emit('cancel')"
      >
        {{ _('Cancel') }}
      </Button>

      <Button
        type="button"
        :disabled="!canUpload"
        @click="$emit('submit', uploadFiles, description)"
      >
        {{ isUploading ? submittingText : submitText }}
      </Button>
    </div>
  </div>
</template>