<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardContent, CardHeader, CardFooter } from '@/components/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Textarea from '@/components/Textarea.vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import LotteryExecutedStatus from '../shared/LotteryExecutedStatus.vue';

import { CalendarIcon, PlayIcon } from 'lucide-vue-next';

const props = defineProps<{
  lottery: Lottery;
}>();

const page = usePage();

const form = useForm({
  start_date: props.lottery.start_date || '',
  description: props.lottery.description,
});

const executionForm = useForm({
  override_count_mismatch: false,
});
const confirmationModalOpen = ref(false);

const mismatchError = computed(() => (page.props as any).mismatchError as string | undefined);

// If there was a mismatch error, next execution should override the count mismatch
watch(mismatchError, (error: string | undefined) => {
  if (error) {
    executionForm.override_count_mismatch = true;
  }
});

const confirmationDescription = computed(() => {
  const baseText = _('This action is irreversible. All units will be permanently assigned to families.');

  if (mismatchError.value) {
    return `${baseText}\n\n⚠️ ${mismatchError.value}`;
  }

  return baseText;
});

const updateLottery = () => form.patch(route('lottery.update', props.lottery.id));

const executeLottery = () => {
  confirmationModalOpen.value = true;
};

const confirmExecution = () => {
  executionForm.post(route('lottery.execute', props.lottery.id), {
    onSuccess: () => {
      confirmationModalOpen.value = false;
      executionForm.override_count_mismatch = false;
    },
    onError: () => {
      confirmationModalOpen.value = false;
    },
  });
};

const isExecutedOrExecuting = computed(() => !props.lottery.is_published);

const canExecute = computed(() => {
  // Can't execute if already executed/executing
  if (isExecutedOrExecuting.value) {
    return false;
  }

  const startDate = props.lottery.start_date ? new Date(props.lottery.start_date) : null;
  return startDate && startDate <= new Date();
});
</script>

<template>
  <Card class="h-full flex flex-col max-w-auto p-base">
    <CardHeader :title="_('Lottery Management')">
      {{ _('Configure lottery settings and execution') }}
    </CardHeader>

    <CardContent class="flex-1 flex flex-col">
      <!-- Execution Status Message -->
      <LotteryExecutedStatus v-if="isExecutedOrExecuting" />

      <!-- Lottery Configuration -->
      <form v-else @submit.prevent="updateLottery" class="flex-1 flex flex-col w-full">
        <div class="space-y-8 flex-1 flex flex-col">
          <!-- Start Date -->
          <div class="space-y-3">
            <Label for="start_date" class="text-base">{{ _('Lottery Date') }}</Label>
            <div class="relative">
              <CalendarIcon class="absolute left-3 top-3.5 size-5 text-text-subtle" />
              <Input
                id="start_date"
                v-model="form.start_date"
                type="datetime-local"
                class="pl-11 h-12 text-base"
                :error="form.errors.start_date"
              />
            </div>
          </div>

          <!-- Description -->
          <div class="space-y-3 flex-1 flex flex-col">
            <Label for="description" class="text-base">{{ _('Description') }}</Label>
            <Textarea
              v-model="form.description"
              id="description"
              :error="form.errors.description"
              class="flex-1 resize-none"
              placeholder="Describe the lottery event and any important details..."
            />
          </div>
        </div>

        <!-- Error Display -->
        <div v-if="form.hasErrors" class="mt-6 rounded-lg bg-red-50 dark:bg-red-950/20 p-4">
          <p class="text-sm text-red-800 dark:text-red-200">
            {{ Object.values(form.errors).flat().join(' ') }}
          </p>
        </div>

        <!-- Save Button -->
        <div class="mt-6 max-w-48">
          <Button type="submit" :loading="form.processing" class="w-full h-11" size="lg">
            {{ _('Update') }}
          </Button>
        </div>
      </form>
    </CardContent>

    <!-- Lottery Execution -->
    <CardFooter class="flex gap-4 border-t pt-6 items-center">
      <div v-if="isExecutedOrExecuting" class="text-sm text-green-600 dark:text-green-400 text-center flex-1">
        {{ _('Lottery has been executed or is currently executing.') }}
      </div>

      <div v-else-if="!canExecute" class="text-sm text-text-muted text-center flex-1">
        {{ _('The lottery will be available for execution after the scheduled date.') }}
      </div>

      <Button
        v-if="!isExecutedOrExecuting"
        @click="executeLottery"
        :disabled="!canExecute"
        variant="default"
        size="lg"
        class="gap-2 h-12"
      >
        <PlayIcon class="h-5 w-5" />
        {{ _('Execute Lottery') }}
      </Button>
    </CardFooter>
  </Card>

  <!-- Confirmation Modal -->
  <ConfirmationModal
    v-model:open="confirmationModalOpen"
    :title="_('Confirm Lottery Execution')"
    :description="confirmationDescription"
    :expected-text="_('EXECUTE')"
    :confirm-button-text="_('Execute Lottery')"
    variant="default"
    @confirm="confirmExecution"
  />
</template>