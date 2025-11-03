<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { _ } from '@/composables/useTranslations';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { auth } from '@/composables/useAuth';
import { computed } from 'vue';

const props = defineProps<{
  userRole: string;
  content: string;
}>();

defineOptions({
  layout: auth.value.user ? AppSidebarLayout : undefined,
});

// Simple markdown to HTML conversion for basic formatting
const htmlContent = computed(() => {
  let html = props.content;

  // Headers
  html = html.replace(/^### (.+)$/gm, '<h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mt-6 mb-3">$1</h3>');
  html = html.replace(/^## (.+)$/gm, '<h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-8 mb-4">$1</h2>');
  html = html.replace(/^# (.+)$/gm, '<h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">$1</h1>');

  // Bold
  html = html.replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold">$1</strong>');

  // Italic
  html = html.replace(/\*(.+?)\*/g, '<em class="italic">$1</em>');

  // Lists
  html = html.replace(/^- (.+)$/gm, '<li class="ml-6 list-disc text-gray-700 dark:text-gray-300">$1</li>');
  html = html.replace(/^(\d+)\. (.+)$/gm, '<li class="ml-6 list-decimal text-gray-700 dark:text-gray-300">$2</li>');

  // Paragraphs
  html = html.replace(/^(?!<[h|li|ul|ol])(.+)$/gm, '<p class="text-base text-gray-700 dark:text-gray-300 mb-4">$1</p>');

  // Links
  html = html.replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2" class="text-blue-600 underline hover:no-underline dark:text-blue-400">$1</a>');

  // Code blocks
  html = html.replace(/`(.+?)`/g, '<code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-200">$1</code>');

  return html;
});
</script>

<template>

    <Head :title="_('Member Guide')" />

    <div class="mx-auto max-w-4xl px-4 py-8 md:px-6 md:py-12">
        <div class="mb-4">
            <a :href="route('home')" class="text-sm text-blue-600 underline hover:no-underline dark:text-blue-400">
                ← {{ _('Back to Home') }}
            </a>
        </div>

        <div class="mb-8 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                {{ _('Have a quick question?') }}
                <a :href="route('documentation.faq', { role: 'member' })"
                    class="font-medium underline hover:no-underline">
                    {{ _('Check the Member FAQ') }}
                </a>
            </p>
        </div>

        <div class="prose prose-gray max-w-none dark:prose-invert" v-html="htmlContent" />
    </div>
</template>
