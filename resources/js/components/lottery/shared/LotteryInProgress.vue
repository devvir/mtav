<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { LoaderCircle, AlertCircle } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const INTERVAL = 1000; // 1 second
const TIMEOUT = 30000; // 30 seconds

const timedOut = ref(false);

// Poll until Lottery execution completes (or polling times out)
const intervalId = setInterval(() => {
  router.reload({ except: ['preferences'] });
}, INTERVAL);

const timeoutId = setTimeout(() => {
  clearInterval(intervalId);
  timedOut.value = true;
}, TIMEOUT);

onUnmounted(() => {
  clearInterval(intervalId);
  clearTimeout(timeoutId);
});
</script>

<template>
  <div class="flex-1 w-full flex items-center justify-center">
    <div class="text-center space-y-4 p-8 max-w-lg">
      <div v-if="!timedOut" class="flex items-center justify-center mx-auto size-16 rounded-full bg-primary/10">
        <LoaderCircle class="size-8 text-primary animate-spin" />
      </div>
      <div v-else class="flex items-center justify-center mx-auto size-16 rounded-full bg-warning/10">
        <AlertCircle class="size-8 text-warning" />
      </div>

      <div class="space-y-2">
        <h3 v-if="!timedOut" class="text-lg font-semibold text-primary">
          {{ _('Lottery in Progress') }}
        </h3>
        <h3 v-else class="text-lg font-semibold text-warning">
          {{ _('Taking Longer Than Expected...') }}
        </h3>

        <p v-if="!timedOut" class="text-sm text-text-muted">
          {{ _('The lottery is currently being executed. Results will be available as soon as the process completes.') }}
        </p>
        <p v-else class="text-sm text-text-muted">
          {{ _('This is taking longer than expected. Please refresh the page or contact an Administrator if the Lottery results do not show after a few minutes.') }}
        </p>
      </div>
    </div>
  </div>
</template>
