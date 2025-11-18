<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { ModalLink } from '@inertiaui/modal-vue';
import { Edit3, FileText, Image, Play, Upload } from 'lucide-vue-next';
import { _ } from '@/composables/useTranslations';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

const props = defineProps<{
  media: ApiResource<Media>;
}>();

const form = useForm({
  description: props.media.description || '',
});

const isSubmitting = ref(false);

const handleSubmit = async () => {
  isSubmitting.value = true;

  form.put(route('media.update', props.media.id), {
    onSuccess: () => {
      router.visit(route('media.show', props.media.id));
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};

const getFileIcon = (mimeType: string | null) => {
  if (!mimeType) return FileText;
  if (mimeType.startsWith('image/')) return Image;
  if (mimeType.startsWith('video/')) return Play;
  if (mimeType === 'application/pdf') return FileText;
  return FileText;
};

const getFileTypeLabel = (mimeType: string | null): string => {
  if (!mimeType) return _('File');
  if (mimeType.startsWith('image/')) return _('Image');
  if (mimeType.startsWith('video/')) return _('Video');
  if (mimeType === 'application/pdf') return 'PDF';
  return _('File');
};

const formatFileSize = (bytes: number | null): string => {
  if (!bytes || bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
  <Head :title="_('Edit Media')" />

  <Breadcrumbs>
    <Breadcrumb route="media.index" text="Media" />
    <Breadcrumb route="media.show" :params="media.id" :text="media.description || 'Media'" />
    <Breadcrumb route="media.edit" :params="media.id" :text="_('Edit')" />
  </Breadcrumbs>

  <MaybeModal>
    <div class="mx-auto w-full max-w-2xl space-y-6 p-6">
      <div class="space-y-2">
        <h1 class="text-2xl font-semibold tracking-tight">{{ _('Edit Media') }}</h1>
        <p class="text-sm text-text-muted">
          {{ _('Update the description for your media') }}
        </p>
      </div>

      <!-- Current Media Preview -->
      <div class="space-y-4">
        <h3 class="font-medium">{{ _('Current File') }}</h3>

        <div class="flex items-center gap-4 rounded-lg border p-4 bg-surface-elevated">
          <!-- Preview/Icon -->
          <div class="flex-shrink-0">
            <div
              v-if="media.category === 'image'"
              class="relative size-16 overflow-hidden rounded border bg-surface-elevated"
            >
              <img
                :src="media.url"
                :alt="media.description || 'Media preview'"
                class="size-full object-cover"
              />
            </div>
            <div
              v-else-if="media.category === 'video'"
              class="relative size-16 overflow-hidden rounded border bg-surface-elevated"
            >
              <video
                :src="media.url"
                class="size-full object-cover"
                preload="metadata"
                muted
              />
              <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                <Play class="size-6 text-white" fill="white" />
              </div>
            </div>
            <div
              v-else
              class="flex size-16 items-center justify-center rounded border bg-surface-elevated"
              :class="{
                'bg-blue-50 border-blue-200': media.mime_type === 'application/pdf',
                'bg-green-50 border-green-200': media.mime_type?.startsWith('video/'),
                'bg-purple-50 border-purple-200': media.mime_type?.startsWith('image/'),
              }"
            >
              <component
                :is="getFileIcon(media.mime_type)"
                class="size-8"
                :class="{
                  'text-blue-600': media.mime_type === 'application/pdf',
                  'text-green-600': media.mime_type?.startsWith('video/'),
                  'text-purple-600': media.mime_type?.startsWith('image/'),
                  'text-text-muted': !media.mime_type?.startsWith('video/') && !media.mime_type?.startsWith('image/') && media.mime_type !== 'application/pdf'
                }"
              />
            </div>
          </div>

          <!-- File Info -->
          <div class="min-w-0 flex-1">
            <div class="font-medium text-sm truncate">
              {{ _('Uploaded file') }}
            </div>
            <div class="flex items-center gap-2 text-xs text-text-muted">
              <span>{{ getFileTypeLabel(media.mime_type) }}</span>
              <span v-if="media.file_size">• {{ formatFileSize(media.file_size) }}</span>
              <span v-if="media.width && media.height">• {{ media.width }}×{{ media.height }}</span>
            </div>
          </div>

          <!-- Replace File Link -->
          <ModalLink
            :href="route('media.create')"
            class="flex items-center gap-2 text-sm text-primary hover:text-primary-600"
          >
            <Upload class="size-4" />
            {{ _('Upload New') }}
          </ModalLink>
        </div>
      </div>

      <!-- Edit Form -->
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Description Input -->
        <div class="space-y-2">
          <Label for="description">{{ _('Description') }}</Label>
          <textarea
            id="description"
            v-model="form.description"
            :placeholder="_('Describe what you\'re sharing...')"
            rows="4"
            class="min-h-[100px] w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          />
          <p class="text-xs text-text-muted">
            {{ _('Add a description to help others understand your upload') }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between border-t pt-6">
          <Button
            type="button"
            variant="ghost"
            @click="router.visit(route('media.show', media.id))"
          >
            {{ _('Cancel') }}
          </Button>

          <Button
            type="submit"
            :disabled="isSubmitting || form.processing"
          >
            <Edit3 class="mr-2 size-4" />
            {{ isSubmitting || form.processing ? _('Saving...') : _('Save Changes') }}
          </Button>
        </div>
      </form>
    </div>
  </MaybeModal>
</template>