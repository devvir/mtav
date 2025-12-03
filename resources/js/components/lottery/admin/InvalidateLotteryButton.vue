<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/card';
import { Button } from '@/components/ui/button';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import { _ } from '@/composables/useTranslations';
import { router } from '@inertiajs/vue3';
import { XCircle } from 'lucide-vue-next';

const props = defineProps<{
  lottery: Lottery;
}>();

const confirmationModalOpen = ref(false);
const invalidating = ref(false);

const invalidateLottery = () => {
  invalidating.value = true;
  router.delete(route('lottery.invalidate', props.lottery.id), {
    onFinish: () => {
      confirmationModalOpen.value = false;
      invalidating.value = false;
    },
    preserveScroll: true,
  });
};
</script>

<template>
  <Card class="flex-0 p-base max-w-full border-warning/50">
    <CardHeader :title="_('Invalidate Lottery Execution')">
      {{ _('Remove all assignments and reset the lottery') }}
    </CardHeader>

    <CardContent>
      <Button @click="confirmationModalOpen = true" variant="destructive" size="lg" class="w-full gap-2">
        <XCircle class="h-5 w-5" />
        {{ _('Invalidate Lottery') }}
      </Button>
    </CardContent>
  </Card>

  <!-- Confirmation Modal -->
  <ConfirmationModal
    v-model:open="confirmationModalOpen"
    :title="_('Confirm Lottery Invalidation')"
    :description="_('This will remove all unit assignments and reset the lottery to its pre-execution state. This action cannot be undone.')"
    :expected-text="_('INVALIDATE')"
    :confirm-button-text="_('Invalidate Lottery')"
    variant="destructive"
    :loading="invalidating"
    @confirm="invalidateLottery"
  />
</template>
