<!-- Copilot - Pending review -->
<script setup lang="ts">
import { useLotteryAudits } from '../composables/useLotteryAudits';
import { router } from '@inertiajs/vue3';
import { _ } from '@/composables/useTranslations';
import { CheckCircle, XCircle, Clock, AlertCircle, Loader2 } from 'lucide-vue-next';

const props = defineProps<{
  lottery: ApiResource<Lottery>;
  families: ApiResource<Family>[];
}>();

const POLL_INTERVAL = 500;                  // 0.5 second
const TIMEOUT_MS = 60000;                   // 30 seconds
const ESTIMATED_SECONDS_PER_UNIT_TYPE = 2;  // Estimated time per unit type group
const SUCCESS_MESSAGE_VISIBILITY = 600;     // In seconds

const {
  hasAudits,
  hasFailure,
  failureAudit,
  groupsCount,
  processedGroups,
  progress,
  isExecuting,
  initAuditTimestamp,
} = useLotteryAudits(computed(() => props.lottery));

const timedOut = ref(false);
const lastAuditCount = ref(0);
const timeoutId = ref<number | null>(null);
const estimatedProgress = ref(0);
const progressIntervalId = ref<number | null>(null);

// Reset timeout whenever audit count changes (new audit arrived)
watch(() => props.lottery.audits?.length ?? 0, (newCount: number) => {
  if (newCount > lastAuditCount.value) {
    lastAuditCount.value = newCount;
    resetTimeout();
  }
});

// Start polling if execution is in progress
const intervalId = ref<number | null>(null);

onMounted(() => {
  if (isExecuting.value) {
    startPolling();
    resetTimeout();
    startEstimatedProgress();
  }
});

onUnmounted(() => {
  stopPolling();
  clearTimeoutTimer();
  stopEstimatedProgress();
});

// Watch for isExecuting becoming true after mount (e.g., after form submission)
watch(isExecuting, (executing: boolean) => {
  if (executing && !intervalId.value) {
    // Execution started, begin polling
    startPolling();
    resetTimeout();
    startEstimatedProgress();
  }
}, { immediate: false });

function startPolling() {
  if (intervalId.value) return;

  intervalId.value = window.setInterval(() => {
    router.reload({ only: ['lottery', 'families'] });
  }, POLL_INTERVAL);
}

function stopPolling() {
  if (intervalId.value) {
    clearInterval(intervalId.value);
    intervalId.value = null;
  }
}

function resetTimeout() {
  clearTimeoutTimer();
  timedOut.value = false;

  timeoutId.value = window.setTimeout(() => {
    stopPolling();
    timedOut.value = true;
  }, TIMEOUT_MS);
}

function clearTimeoutTimer() {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value);
    timeoutId.value = null;
  }
}

function startEstimatedProgress() {
  if (progressIntervalId.value) return;
  if (!initAuditTimestamp.value || groupsCount.value === 0) return;

  progressIntervalId.value = window.setInterval(() => {
    updateEstimatedProgress();
  }, 100); // Update every 100ms for smooth animation
}

function stopEstimatedProgress() {
  if (progressIntervalId.value) {
    clearInterval(progressIntervalId.value);
    progressIntervalId.value = null;
  }
}

function updateEstimatedProgress() {
  if (!initAuditTimestamp.value || groupsCount.value === 0) return;

  const now = Date.now();
  const elapsed = (now - initAuditTimestamp.value) / 1000; // seconds

  // Calculate progress: from current completed % to next milestone
  const segmentSize = 100 / groupsCount.value;
  const currentSegmentStart = processedGroups.value * segmentSize; // e.g., 33.3% if 1 of 3 done

  // Time elapsed since last completion (or since init if nothing completed yet)
  const segmentElapsed = elapsed - (processedGroups.value * ESTIMATED_SECONDS_PER_UNIT_TYPE);

  // Progress within current segment (0 to 1, but cap at 0.99 to not reach 100% prematurely)
  const segmentProgress = Math.min(segmentElapsed / ESTIMATED_SECONDS_PER_UNIT_TYPE, 0.99);

  // Estimated progress: start + (progress_fraction * segment_size)
  // e.g., 33.3% + (13/40 * 33.3%) = 33.3% + 10.8% = 44.1%
  estimatedProgress.value = currentSegmentStart + (segmentProgress * segmentSize);
}

// Watch processed groups to jump to actual progress when it changes
watch(processedGroups, (newCount: number) => {
  if (groupsCount.value > 0) {
    const actualProgress = (newCount / groupsCount.value) * 100;
    // Jump to actual progress if it's ahead of estimate
    if (actualProgress > estimatedProgress.value) {
      estimatedProgress.value = actualProgress;
    }
  }
});

// Stop polling when execution completes or fails
watch([isExecuting, hasFailure], ([executing, failed]: [boolean, boolean]) => {
  if (!executing || failed) {
    stopPolling();
    clearTimeoutTimer();
    stopEstimatedProgress();
    if (!failed && !executing) {
      estimatedProgress.value = 100;
    }
  }
});

// Phase information based on audit types
const phaseInfo = computed(() => {
  if (!hasAudits.value || hasFailure.value) return null;

  const audits = props.lottery.audits || [];
  const phases: { name: string; completed: boolean; current: boolean }[] = [];

  const hasInit = audits.some((a: LotteryAudit) => a.type === 'init');
  const groupCount = audits.filter((a: LotteryAudit) => a.type === 'group_execution').length;
  const hasProject = audits.some((a: LotteryAudit) => a.type === 'project_execution');

  if (hasInit) {
    phases.push({ name: _('Initialization'), completed: true, current: false });
  }

  if (groupsCount.value > 0 || groupCount > 0) {
    const isProcessing = !hasProject && !hasFailure.value && groupCount < groupsCount.value;
    phases.push({
      name: _('Processing Groups') + ` (${groupCount}/${groupsCount.value})`,
      completed: hasProject,
      current: isProcessing,
    });
  }

  if (hasProject) {
    phases.push({ name: _('Finalization'), completed: true, current: false });
  }

  return phases;
});

// Only show audit UI when lottery is executing, completed, or failed (not invalidated)
const shouldShowAudit = computed(() => {
  return hasAudits.value && (
    (props.lottery.is_completed && showCompletedFeedback.value)
    || props.lottery.is_executing
    || hasFailure.value
  ) ;
});

// Completed execution feedback is shown only for a certain period
const showCompletedFeedback = computed(() => {
  if (! props.lottery.audits!.length) return false;

  const lastAudit = props.lottery.audits[props.lottery.audits.length - 1];
  const ageMs = Date.now() - new Date(lastAudit.created_at).getTime();

  return ageMs <= SUCCESS_MESSAGE_VISIBILITY * 1000;
});
</script>

<template>
  <div v-if="shouldShowAudit" class="w-full bg-surface border border-border rounded-lg p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <div v-if="hasFailure" class="flex items-center justify-center size-10 rounded-full bg-error-subtle">
        <XCircle class="size-5 text-error" />
      </div>
      <div v-else-if="isExecuting" class="flex items-center justify-center size-10 rounded-full bg-interactive/10">
        <Loader2 class="size-5 text-interactive animate-spin" />
      </div>
      <div v-else-if="timedOut" class="flex items-center justify-center size-10 rounded-full bg-warning-subtle">
        <AlertCircle class="size-5 text-warning" />
      </div>
      <div v-else class="flex items-center justify-center size-10 rounded-full bg-success-subtle">
        <CheckCircle class="size-5 text-success" />
      </div>

      <div class="flex-1">
        <h3 v-if="hasFailure" class="text-lg font-semibold text-error">
          {{ _('Lottery Execution Failed') }}
        </h3>
        <h3 v-else-if="isExecuting" class="text-lg font-semibold text-interactive">
          {{ _('Lottery in Progress') }}
        </h3>
        <h3 v-else-if="timedOut" class="text-lg font-semibold text-warning">
          {{ _('Taking Longer Than Expected...') }}
        </h3>
        <h3 v-else class="text-lg font-semibold text-success">
          {{ _('Lottery Completed!') }}
        </h3>
      </div>
    </div>

    <!-- Failure Details -->
    <div v-if="hasFailure && failureAudit" class="bg-error-subtle border border-error rounded-md p-4">
      <p class="text-sm font-medium text-error mb-2">
        {{ failureAudit.audit.user_message || _('An error occurred during execution') }}
      </p>
      <p class="text-xs text-error-subtle-foreground">
        {{ _('Error Type') }}: {{ failureAudit.audit.error_type || _('Unknown') }}
      </p>
      <p class="text-xs text-error-subtle-foreground">
        {{ failureAudit.audit.exception?.split('\\').pop() || '' }}
      </p>
    </div>

    <!-- Timeout Warning -->
    <div v-if="timedOut && !hasFailure" class="bg-warning-subtle border border-warning rounded-md p-4">
      <p class="text-sm text-warning-subtle-foreground">
        {{ _('This is taking longer than expected. Please refresh the page or contact an Administrator if the Lottery results do not show after a few minutes.') }}
      </p>
    </div>

    <!-- Phase Progress -->
    <div v-if="phaseInfo && phaseInfo.length > 0" class="space-y-2">
      <div v-for="(phase, idx) in phaseInfo" :key="idx" class="flex items-center gap-3">
        <div v-if="phase.completed" class="flex items-center justify-center size-6 rounded-full bg-success-subtle">
          <CheckCircle class="size-4 text-success" />
        </div>
        <div v-else-if="phase.current" class="flex items-center justify-center size-6 rounded-full bg-interactive/10">
          <Loader2 class="size-4 text-interactive animate-spin" />
        </div>
        <div v-else class="flex items-center justify-center size-6 rounded-full bg-surface-interactive">
          <Clock class="size-4 text-text-muted" />
        </div>

        <span
          class="text-sm"
          :class="[
            phase.completed ? 'text-success font-medium' : '',
            phase.current ? 'text-interactive font-medium' : '',
            !phase.completed && !phase.current ? 'text-text-muted' : ''
          ]"
        >
          {{ phase.name }}
        </span>
      </div>
    </div>

    <!-- Progress Bar -->
    <div v-if="isExecuting && groupsCount > 0" class="space-y-2">
      <div class="flex justify-between text-sm">
        <span class="text-text-muted">{{ _('Groups Processed') }}</span>
        <span class="font-medium text-text">{{ processedGroups }} / {{ groupsCount }} ({{ Math.round(progress) }}%)</span>
      </div>
      <div class="h-2 bg-border rounded-full overflow-hidden">
        <div
          class="h-full bg-interactive transition-all duration-100 ease-linear"
          :style="{ width: `${Math.max(progress, estimatedProgress)}%` }"
        />
      </div>
    </div>

    <!-- Completion Message -->
    <div v-if="!isExecuting && !hasFailure && !timedOut" class="text-sm text-success font-medium">
      {{ _('All families have been assigned to their units.') }}
    </div>
  </div>
</template>
