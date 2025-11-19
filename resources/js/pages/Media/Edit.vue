<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import MaybeModal from '@/components/MaybeModal.vue';
import { EditForm } from '@/components/entities/media';

defineEmits<{ modalEvent: any[] }>(); // Hotfix to remove InertiaUI Modal warnings

defineProps<{
  media: ApiResource<Media>;
  category: MediaCategory;
  categories: Record<MediaCategory, string>;
}>();
</script>

<template>
  <Head :title="`${categories[category]} - ${_('Edit')}`" no-translation />

  <Breadcrumbs>
    <Breadcrumb route="media.index" :text="categories[category]" no-translation />
    <Breadcrumb route="media.edit" :params="media.id" text="Edit" />
  </Breadcrumbs>

  <MaybeModal v-slot="{ close }">
    <EditForm
      :media
      :category
      :categories
      @cancel="close"
      @submit="close"
    />
  </MaybeModal>
</template>