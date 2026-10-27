<script setup lang="ts">
import PageHeader from '@/components/layout/PageHeader.vue';
import { route } from '@/composables/useRoute';
import { Link } from '@inertiajs/vue3';

defineProps<{
  title: string;
  description: string;
  guideText: string;
  questions: { question: string; answer: string }[];
}>();
</script>

<template>
  <PageHeader :title :description />
  <div class="max-w-3xl space-y-3">
    <details
      v-for="item in questions"
      :key="item.question"
      class="group rounded-xl border-2 border-border bg-surface px-4 py-3"
    >
      <summary class="cursor-pointer list-none font-semibold">{{ item.question }}</summary>
      <p class="mt-2 text-text-muted">{{ item.answer }}</p>
    </details>
  </div>
  <Link
    :href="route('documentation.guide')"
    class="mt-6 inline-block font-medium text-text-link hover:underline"
    >{{ guideText }}</Link
  >
</template>
