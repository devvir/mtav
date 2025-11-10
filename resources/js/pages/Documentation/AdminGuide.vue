<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { _ } from '@/composables/useTranslations';

defineProps<{
  userRole: string;
  content: string;
}>();
</script>

<script lang="ts">
export default {
  layout: null,
};
</script>

<template>

    <Head :title="_('Admin Guide')" />

    <div class="mx-auto max-w-4xl px-4 py-8 md:px-6 md:py-12">
        <div class="mb-4 md:hidden">
            <a :href="route('dashboard')" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                ← {{ _('Back to Dashboard') }}
            </a>
        </div>

        <div class="mb-8 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                {{ _('Have a quick question?') }}
                <a :href="route('documentation.faq')"
                    class="font-medium underline hover:no-underline">
                    {{ _('Check the Admin FAQ') }}
                </a>
            </p>
        </div>

        <div class="prose prose-gray max-w-none dark:prose-invert" v-html="content" />
    </div>
</template>
