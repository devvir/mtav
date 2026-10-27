<script setup lang="ts">
import PageHeader from '@/components/layout/PageHeader.vue';
import { _ } from '@/composables/useTranslations';

/** The guide's HTML comes from resources/views/guides (static, written by us) */
defineProps<{ content: string; userRole: string }>();
</script>

<template>
  <PageHeader :title="_('Guide')" />
  <article class="prose max-w-3xl dark:prose-invert" v-html="content" />
</template>
