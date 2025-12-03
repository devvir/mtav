<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { CheckCircle2 } from 'lucide-vue-next';
import LotteryResultCard from './LotteryResultCard.vue';

defineProps<{
  families: ApiResource<Family>[];
}>();
</script>

<template>
  <div class="flex-1 w-full space-y-4 p-4">
    <!-- Header -->
    <div class="space-y-2">
      <div class="flex items-center gap-2">
        <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center shrink-0">
          <CheckCircle2 class="h-5 w-5 text-green-600 dark:text-green-400" />
        </div>

        <h3 class="flex-1 text-base font-semibold text-green-700 dark:text-green-300">
          {{ _('Lottery Completed!') }}
        </h3>
      </div>
      <p class="text-sm text-text-muted">
        {{ _('All units have been assigned to families according to their preferences and the optimization algorithm.') }}
      </p>
    </div>

    <!-- Results Grid -->
    <div class="mt-wide grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] auto-rows-auto gap-3">
      <LotteryResultCard v-for="family in families" :key="family.id" :family />
    </div>
  </div>
</template>
