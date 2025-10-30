<script setup lang="ts">
import { _ } from '@/composables/useTranslations';

interface Props {
  title: string;
  description?: string;
}

defineProps<Props>();
</script>

<template>
  <header>
    <h3 class="mb-1 text-xl font-medium text-text">{{ _(title) }}</h3>
    <p v-if="description" class="text-xs text-text-muted">
      {{ _(description) }}
    </p>
  </header>
</template>
