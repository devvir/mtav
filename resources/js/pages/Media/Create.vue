<script setup lang="ts">
import Head from '@/components/Head.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { UploadForm } from '@/components/entities/media';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  category: MediaCategory;
  categories: Record<MediaCategory, string>;
}>();
</script>

<template>
  <Head :title="`${categories[category]} - _('Upload')`" no-translation />

  <Breadcrumbs>
    <Breadcrumb route="media.index" :text="categories[category]" no-translation />
    <Breadcrumb route="media.create" text="Upload" />
  </Breadcrumbs>

  <MaybeModal v-slot="{ close }">
    <UploadForm :category @submit="close" @cancel="close" />
  </MaybeModal>
</template>