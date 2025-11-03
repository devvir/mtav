<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { _ } from '@/composables/useTranslations';
import { ref } from 'vue';

defineProps<{
  userRole: string;
}>();

const openItems = ref<Set<number>>(new Set());

const toggleItem = (index: number) => {
  if (openItems.value.has(index)) {
    openItems.value.delete(index);
  } else {
    openItems.value.add(index);
  }
};

const faqs = [
  {
    question: 'How do I update my profile information?',
    answer: 'Navigate to your profile by clicking your avatar in the top right corner. You can update your personal information, contact details, and preferences. Don\'t forget to save your changes.',
  },
  {
    question: 'How do I set my unit preferences?',
    answer: 'Go to the Preferences section in the sidebar. You can select your preferred units and rank them in order of preference. These preferences will be used during the lottery process.',
  },
  {
    question: 'How does the lottery system work?',
    answer: 'The lottery system randomly assigns units based on member preferences and availability. Make sure to set your preferences before the lottery date. You\'ll be notified of the results via email and in the system.',
  },
  {
    question: 'How do I RSVP for events?',
    answer: 'Events are listed in the Events section. Click on an event to view details and RSVP. You can indicate whether you\'re attending and how many family members will join you.',
  },
  {
    question: 'Can I invite family members?',
    answer: 'Yes! Family members can be added from your profile or the Families section. They will be associated with your household and can also set their own preferences.',
  },
  {
    question: 'How do I upload photos or videos?',
    answer: 'Use the Media section to upload photos and videos related to the cooperative. You can organize them by project or event. Please ensure your uploads comply with the community guidelines.',
  },
  {
    question: 'How do I view other members?',
    answer: 'The Members section shows all cooperative members. You can view member profiles, see who else is in your project, and connect with your community.',
  },
  {
    question: 'What if I want to leave the cooperative?',
    answer: 'If you need to leave, please contact an administrator through the system or via email. There is a formal process to follow, and administrators will guide you through the necessary steps.',
  },
];
</script>

<script lang="ts">
export default {
  layout: null,
};
</script>

<template>

    <Head :title="_('FAQ - Member')" />

    <div class="mx-auto max-w-4xl px-4 py-8 md:px-6 md:py-12">
        <div class="mb-4">
            <a :href="route('home')" class="text-sm text-blue-600 underline hover:no-underline dark:text-blue-400">
                ← {{ _('Back to Home') }}
            </a>
        </div>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                {{ _('Member FAQ') }}
            </h1>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
                {{ _('Frequently asked questions for members') }}
            </p>
        </div>

        <div class="mb-8 rounded-lg bg-blue-50 p-6 dark:bg-blue-900/20">
            <p class="text-base leading-relaxed text-blue-800 dark:text-blue-200">
                {{ _('Need more detailed information?') }}
                <a :href="route('documentation.guide', { role: 'member' })"
                    class="ml-1 font-medium underline hover:no-underline">
                    {{ _('Read the comprehensive Member Guide') }}
                </a>
            </p>
        </div>

        <div class="space-y-3">
            <div v-for="(faq, index) in faqs" :key="index"
                class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <button @click="toggleItem(index)"
                    class="flex w-full items-center justify-between p-4 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ _(faq.question) }}
                    </h3>
                    <span class="ml-4 text-gray-500 transition-transform dark:text-gray-400"
                        :class="{ 'rotate-180': openItems.has(index) }">
                        ▼
                    </span>
                </button>
                <div v-show="openItems.has(index)" class="border-t border-gray-200 px-4 pb-4 pt-3 dark:border-gray-700">
                    <p class="text-base leading-relaxed text-gray-600 dark:text-gray-400">
                        {{ _(faq.answer) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
