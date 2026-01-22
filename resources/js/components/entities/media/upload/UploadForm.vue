<script setup lang="ts">
import { FileList, FileUpload, ProgressBar, UploadHeader } from '.';
import { Description, FormActions, FormErrors } from '..';
import type { MediaUploadForm } from '../types';

const emit = defineEmits<{
  submit: [];
  cancel: [];
}>();

const props = defineProps<{
  category: MediaCategory;
}>();

const form = useForm<MediaUploadForm>({
  files: {} as Record<string, File>,
  description: '',
  category: props.category,
});

const uploadFiles = () =>
  form.post(route('media.store'), {
    onSuccess: () => emit('submit'),
  });
</script>

<template>
  <div class="mx-auto w-full max-w-2xl space-y-6 px-6">
    <UploadHeader :category />

    <main class="space-y-6">
      <FileUpload :form v-model="form.files" :category />
      <Description :form v-model="form.description" />
      <ProgressBar :form />
      <FormErrors :form />
    </main>

    <footer class="space-y-6">
      <FormActions :form @cancel="$emit('cancel')" @submit="uploadFiles" />
      <FileList :form v-model="form.files" />
    </footer>
  </div>
</template>
