<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import Head from '@/components/Head.vue';

interface FaqItem {
  question: string;
  answer: string;
}

interface Props {
  questions: FaqItem[];
  title: string;
  description: string;
  guideText: string;
}

defineProps<Props>();

const openItems = ref<Set<number>>(new Set());

const toggleItem = (index: number) => {
  if (openItems.value.has(index)) {
    openItems.value.delete(index);
  } else {
    openItems.value.add(index);
  }
};
</script>

<template>
  <Head :title="title" />

  <div class="mx-auto max-w-4xl px-4 py-8 md:px-6 md:py-12">
    <div class="mb-4 md:hidden">
      <a
        :href="route('dashboard')"
        class="text-sm text-blue-600 hover:underline dark:text-blue-400"
      >
        ← {{ _('Back to Dashboard') }}
      </a>
    </div>

    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
        {{ title }}
      </h1>
      <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
        {{ description }}
      </p>
    </div>

    <div class="mb-8 rounded-lg bg-blue-50 p-6 dark:bg-blue-900/20">
      <p class="text-base leading-relaxed text-blue-800 dark:text-blue-200">
        {{ _('Need more detailed information?') }}
        <a
          :href="route('documentation.guide')"
          class="ml-1 font-medium underline hover:no-underline"
        >
          {{ guideText }}
        </a>
      </p>
    </div>

    <div class="space-y-3">
      <div
        v-for="(faq, index) in questions"
        :key="index"
        class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
      >
        <button
          @click="toggleItem(index)"
          class="flex w-full items-center justify-between p-4 text-left text-base font-medium text-gray-900 hover:bg-gray-50 dark:text-gray-100 dark:hover:bg-gray-700"
        >
          <span>{{ _(faq.question) }}</span>
          <svg
            :class="{ 'rotate-180': openItems.has(index) }"
            class="h-5 w-5 transition-transform duration-200"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 9l-7 7-7-7"
            ></path>
          </svg>
        </button>
        <div
          v-show="openItems.has(index)"
          class="border-t border-gray-200 bg-gray-50 p-4 text-sm leading-relaxed text-gray-700 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300"
        >
          {{ _(faq.answer) }}
        </div>
      </div>
    </div>

    <div class="mt-12 rounded-lg bg-gray-50 p-6 dark:bg-gray-800">
      <p class="text-base text-gray-700 dark:text-gray-300">
        {{ _('Still have questions?') }}
        {{
          _(
            ' You can contact an admin by going to Dashboard > Admins > click on an admin > Click Contact.',
          )
        }}
      </p>
    </div>
  </div>
</template>
