<!-- Copilot - Pending review -->
<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { PlayIcon } from 'lucide-vue-next';
</script>

<template>
  <div class="flex-1 w-full flex items-center justify-center">
    <div class="text-center space-y-4 p-8">
      <div class="mx-auto w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
        <PlayIcon class="h-8 w-8 text-green-600 dark:text-green-400" />
      </div>
      <div class="space-y-2">
        <h3 class="text-lg font-semibold text-green-700 dark:text-green-300">
          {{ _('Lottery Executed') }}
        </h3>
        <p class="text-sm text-text-muted max-w-md">
          {{ _('The lottery has been executed. All units have been assigned to families according to their preferences and the established algorithm.') }}
        </p>
      </div>
    </div>
  </div>
</template>
