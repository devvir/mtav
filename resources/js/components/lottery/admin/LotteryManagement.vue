<script setup lang="ts">
import { _ } from '@/composables/useTranslations';
import { Card, CardContent, CardHeader } from '@/components/card';
import { Button } from '@/components/ui/button';
import FormInput from '@/components/forms/FormInput.vue';
import Textarea from '@/components/Textarea.vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { Alert, AlertDescription } from '@/components/alert';

import { PlayIcon, AlertTriangle } from 'lucide-vue-next';
import { Label } from '@/components/ui/label';

const props = defineProps<{
  lottery: Lottery;
  families: ApiResource<Family>[];
  options: string[];
  warning: string | null;
}>();

const confirmationModalOpen = ref(false);

const execute = () => {
  // Create form with the complete options data
  const form = useForm({ options: props.options });

  form.post(route('lottery.execute', props.lottery.id), {
    onFinish: () => confirmationModalOpen.value = false,
    preserveScroll: true,
  });
};

const canExecute = computed(
  () => props.lottery.start_date && new Date(props.lottery.start_date) < new Date()
);

const updateForm = useForm({
  start_date: props.lottery.start_date || '',
  description: props.lottery.description,
});

const updateLottery = () => updateForm.patch(route('lottery.update', props.lottery.id), {
  preserveScroll: true,
});
</script>

<template>
  <div class="space-y-6">
    <!-- Lottery Execution -->
    <Card class="p-base max-w-full">
      <CardHeader :title="_('Lottery Execution')">
        {{ _('Execute the lottery when ready') }}
      </CardHeader>

      <CardContent>
        <!-- Option Confirmation Alert -->
        <Alert v-if="warning" variant="warning" class="mb-4">
          <AlertTriangle class="h-4 w-4" />
          <AlertDescription>{{ warning }}</AlertDescription>
        </Alert>

        <div v-if="!canExecute" class="text-sm text-text-muted text-center py-4">
          {{ _('The lottery will be available for execution after the scheduled date.') }}
        </div>

        <Button v-else @click="confirmationModalOpen = true" variant="default" size="lg" class="w-full gap-2">
          <PlayIcon class="h-5 w-5" />
          {{ warning ? _('Confirm and Execute') : _('Execute Lottery') }}
        </Button>
      </CardContent>
    </Card>

    <!-- Lottery Configuration Form -->
    <Card class="max-w-full">
      <CardHeader :title="_('Lottery Settings')">
        {{ _('Configure lottery date and description') }}
      </CardHeader>

      <CardContent>
        <form @submit.prevent="updateLottery" class="w-full space-y-6">
          <!-- Start Date -->
          <FormInput v-model="updateForm.start_date" name="start_date" type="datetime-local" :label="_('Lottery Date')" />

          <!-- Description -->
          <div class="space-y-2">
            <Label for="description" class="text-base">{{ _('Description') }}</Label>
            <Textarea
              v-model="updateForm.description"
              id="description"
              :error="updateForm.errors.description"
              :rows="4"
              :placeholder="_('Describe the lottery event and any important details...')"
            />
          </div>

          <!-- Error Display -->
          <div v-if="updateForm.hasErrors" class="rounded-lg bg-red-50 dark:bg-red-950/20 p-4">
            <p class="text-sm text-red-800 dark:text-red-200">
              {{ Object.values(updateForm.errors).flat().join(' ') }}
            </p>
          </div>

          <!-- Save Button -->
          <Button type="submit" :loading="updateForm.processing" variant="outline">
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
    :description="warning || _('This action is irreversible. All units will be permanently assigned to families.')"
    :expected-text="warning ? _('CONFIRM') : _('EXECUTE')"
    :confirm-button-text="warning ? _('Confirm') : _('Execute Lottery')"
    variant="default"
    @confirm="execute"
  />
</template>