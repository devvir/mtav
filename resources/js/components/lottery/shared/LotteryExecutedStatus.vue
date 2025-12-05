<script setup lang="ts">
import { Card } from '@/components/card';
import { iAmSuperadmin } from '@/composables/useAuth';
import LotteryInProgress from './LotteryInProgress.vue';
import LotteryResults from './LotteryResults.vue';
import InvalidateLotteryButton from '../admin/InvalidateLotteryButton.vue';

const props = defineProps<{
  lottery: Lottery;
  families: ApiResource<Family>[];
}>();

const isCompleted = computed(() => props.lottery.is_completed);
const isInProgress = computed(() => props.lottery.is_executing);
</script>

<template>
  <div class="h-full flex flex-col space-y-6">
    <Card class="flex flex-col max-w-auto">
      <!-- In Progress -->
      <LotteryInProgress v-if="isInProgress" class="flex-1" />

      <!-- Completed with Results -->
      <LotteryResults v-else-if="isCompleted" :families="families" />
    </Card>

    <!-- Invalidate Button (Superadmin only) -->
    <InvalidateLotteryButton v-if="iAmSuperadmin" :lottery />
  </div>
</template>
