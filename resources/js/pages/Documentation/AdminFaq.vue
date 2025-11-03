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
    question: 'How do I invite new members?',
    answer: 'Navigate to the Members section in the sidebar, then click the "Invite Member" button. Fill in the member\'s email address and any additional information. They will receive an email invitation with instructions to complete their registration.',
  },
  {
    question: 'How do I manage user permissions?',
    answer: 'User permissions are managed through roles. Admins have full access to all features. To modify a user\'s role, go to Members, select the user, and update their role in the user details panel.',
  },
  {
    question: 'How do I view system logs?',
    answer: 'System logs are accessible from the Logs section in the sidebar. You can filter by date, user, or action type to find specific events. All administrative actions are logged for audit purposes.',
  },
  {
    question: 'How do I configure project settings?',
    answer: 'Project settings can be accessed from the Projects section. Select the project you want to configure and click the settings icon. You can modify project details, unit types, and other project-specific configurations.',
  },
  {
    question: 'How do I run the lottery system?',
    answer: 'The lottery system is accessed from the Lottery section. Review member preferences, configure lottery parameters, and run the draw. The system will automatically assign units based on the configured rules and member preferences.',
  },
  {
    question: 'What if I need to cancel an invitation?',
    answer: 'To cancel a pending invitation, go to the Members section, filter by "Invited" status, find the invitation, and click the cancel/delete button. The user will no longer be able to use that invitation link.',
  },
  {
    question: 'How do I export member data?',
    answer: 'Member data can be exported from the Members section. Use the export button to download a CSV or Excel file with all member information. You can filter members before exporting to create custom reports.',
  },
  {
    question: 'How do I manage families?',
    answer: 'Families are managed through the Families section. You can view all family groups, add or remove family members, and manage family-related information. Each member can be part of one family.',
  },
];
</script>

<script lang="ts">
export default {
  layout: null,
};
</script>

<template>

    <Head :title="_('FAQ - Admin')" />

    <div class="mx-auto max-w-4xl px-4 py-8 md:px-6 md:py-12">
        <div class="mb-4">
            <a :href="route('home')" class="text-sm text-blue-600 underline hover:no-underline dark:text-blue-400">
                ← {{ _('Back to Home') }}
            </a>
        </div>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                {{ _('Admin FAQ') }}
            </h1>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
                {{ _('Frequently asked questions for administrators') }}
            </p>
        </div>

        <div class="mb-8 rounded-lg bg-blue-50 p-6 dark:bg-blue-900/20">
            <p class="text-base leading-relaxed text-blue-800 dark:text-blue-200">
                {{ _('Need more detailed information?') }}
                <a :href="route('documentation.guide', { role: 'admin' })"
                    class="ml-1 font-medium underline hover:no-underline">
                    {{ _('Read the comprehensive Admin Guide') }}
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
