<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import UploadForm from '@/entities/media/UploadForm.vue';
import { _ } from '@/composables/useTranslations';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

const supportedTypes = ['image/*', 'video/*'];

const validationMimeTypes = [
  'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
  'video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov',
];

const uploadFormRef = ref();

const handleSubmit = async (files: any[], description: string) => {
  const validFiles = files.filter((f: any) => f.status === 'pending');
  if (validFiles.length === 0) return;

  uploadFormRef.value.setUploading(true);

  try {
    // Mark all files as uploading
    validFiles.forEach((file: any) => {
      uploadFormRef.value.setFileStatus(file.id, 'uploading');
    });

    // Create a single form with all files
    const uploadForm = useForm({
      files: validFiles.map((f: any) => f.file),
      description: description,
    });

    // Submit all files in one request
    await new Promise<void>((resolve, reject) => {
      uploadForm.post(route('media.store'), {
        forceFormData: true,
        onSuccess: () => {
          // Mark all files as completed
          validFiles.forEach((file: any) => {
            uploadFormRef.value.setFileStatus(file.id, 'completed');
          });
          resolve();
        },
        onError: (errors: any) => {
          // Check if it's a validation error (form stays on page)
          if (errors && Object.keys(errors).length > 0) {
            // Reset files to pending status for validation errors
            validFiles.forEach((file: any) => {
              uploadFormRef.value.setFileStatus(file.id, 'pending');
            });
            reject(new Error('Validation failed'));
          } else {
            // Mark all files as failed for actual upload errors
            validFiles.forEach((file: any) => {
              uploadFormRef.value.setFileStatus(file.id, 'failed', _('Upload failed. Please try again.'));
            });
            reject(new Error('Upload failed'));
          }
        },
      });
    });

    // Redirect to media index after successful upload
    router.visit(route('media.index'));
  } catch (error: any) {
    // Only mark files as failed if it's not a validation error
    if (error?.message !== 'Validation failed') {
      validFiles.forEach((file: any) => {
        uploadFormRef.value.setFileStatus(file.id, 'failed', _('Upload failed. Please try again.'));
      });
    }
    // For validation errors, files remain in 'pending' status
  } finally {
    uploadFormRef.value.setUploading(false);
  }
};

const handleCancel = () => {
  router.visit(route('media.index'));
};
</script>

<template>
  <Head :title="_('Upload Media')" />

  <Breadcrumbs>
    <Breadcrumb route="media.index" text="Media" />
    <Breadcrumb route="media.create" :text="_('Upload')" />
  </Breadcrumbs>

  <MaybeModal :panelClasses="['panelClasses', 'rounded-l-xl bg-card-elevated/95! p-base']">
    <UploadForm
      ref="uploadFormRef"
      :supported-types="supportedTypes"
      :validation-mime-types="validationMimeTypes"
      :title="_('Upload Media')"
      :subtitle="_('Share photos, videos, or documents with your community')"
      :drop-text="_('Drop files here or click to browse')"
      :support-text="_('Images and videos up to 10MB each')"
      :button-text="_('Choose Files')"
      :submit-text="_('Upload Files')"
      :submitting-text="_('Uploading...')"
      :validation-message="'Unsupported file type. Please use images or videos.'"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </MaybeModal>
</template>