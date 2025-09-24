<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
  title?: string;
  noTranslation?: boolean;
}>();

const translatedTitle = computed(() => (props.title ? _(props.title) : undefined));
</script>

<template>
  <Head :title="noTranslation ? title : translatedTitle" />
</template>
