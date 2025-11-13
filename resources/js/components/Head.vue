<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Head } from '@inertiajs/vue3';

defineProps<{
  title?: string;
  noTranslation?: boolean;
}>();
</script>

<template>
  <Head :title="noTranslation ? title : _(title)" />
</template>
