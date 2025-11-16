<script setup lang="ts">
import ShowPage from '@/components/entities/ShowPage.vue';
import { _ } from '@/composables/useTranslations';

defineProps<{
  media: ApiResource<Media>;
}>();
</script>

<template>
  <ShowPage
    :pageTitle="_('Gallery')"
    entity="media"
    :resource="media"
    modal-width="4xl"
    class="p-0 [&>*]:p-0! max-w-full max-h-[98vh]" />
</template>

<style>
  [data-inertiaui-modal-id] [data-state='open'].im-backdrop {
    background-color: color-mix(in hsl, var(--background) 80%, transparent) !important;
    backdrop-filter: blur(12px) !important;
  }
</style>