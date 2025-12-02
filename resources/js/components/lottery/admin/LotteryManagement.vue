<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardContent, CardHeader } from '@/components/card';
import { Button } from '@/components/ui/button';
import FormInput from '@/components/forms/FormInput.vue';
import Textarea from '@/components/Textarea.vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import LotteryExecutedStatus from '../shared/LotteryExecutedStatus.vue';

import { PlayIcon } from 'lucide-vue-next';
import { Label } from '@/components/ui/label';

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

const updateLottery = () => form.patch(route('lottery.update', props.lottery.id), { preserveScroll: true });

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

// Lottery is executing when it's no longer published and executed when it's soft-deleted
const isExecutedOrExecuting = computed(() => props.lottery.is_deleted || !props.lottery.is_published);

const canExecute = computed(
  () => !isExecutedOrExecuting.value
    && props.lottery.start_date
    && new Date(props.lottery.start_date) < new Date()
);
</script>

<template>
  <div class="space-y-6" :class="{ 'h-full': isExecutedOrExecuting }">
    <!-- Lottery Execution -->
    <Card class="p-base max-w-full">
      <CardHeader :title="_('Lottery Execution')">
        <span v-if="! isExecutedOrExecuting">{{ _('Execute the lottery when ready') }}</span>
      </CardHeader>

      <CardContent>
        <LotteryExecutedStatus v-if="isExecutedOrExecuting" />

        <div v-else-if="!canExecute" class="text-sm text-text-muted text-center py-4">
          {{ _('The lottery will be available for execution after the scheduled date.') }}
        </div>

        <Button
          v-else
          @click="executeLottery"
          variant="default"
          size="lg"
          class="w-full gap-2"
        >
          <PlayIcon class="h-5 w-5" />
          {{ _('Execute Lottery') }}
        </Button>
      </CardContent>
    </Card>

    <!-- Lottery Configuration Form (always shown) -->
    <Card v-if="! isExecutedOrExecuting" class="max-w-full">
      <CardHeader :title="_('Lottery Settings')">
        {{ _('Configure lottery date and description') }}
      </CardHeader>

      <CardContent>
        <form @submit.prevent="updateLottery" class="w-full space-y-6">
          <!-- Start Date -->
          <FormInput
            v-model="form.start_date"
            name="start_date"
            type="datetime-local"
            :label="_('Lottery Date')"
          />

          <!-- Description -->
          <div class="space-y-2">
            <Label for="description" class="text-base">{{ _('Description') }}</Label>
            <Textarea
              v-model="form.description"
              id="description"
              :error="form.errors.description"
              :rows="4"
              :placeholder="_('Describe the lottery event and any important details...')"
            />
          </div>

          <!-- Error Display -->
          <div v-if="form.hasErrors" class="rounded-lg bg-red-50 dark:bg-red-950/20 p-4">
            <p class="text-sm text-red-800 dark:text-red-200">
              {{ Object.values(form.errors).flat().join(' ') }}
            </p>
          </div>

          <!-- Save Button -->
          <Button type="submit" :loading="form.processing" variant="outline">
            {{ _('Update') }}
          </Button>
        </form>
      </CardContent>
    </Card>
  </div>

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