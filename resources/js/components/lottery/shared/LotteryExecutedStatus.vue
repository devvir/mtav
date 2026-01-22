<script setup lang="ts">
import { Card } from '@/components/card';
import { iAmSuperadmin } from '@/composables/useAuth';
import InvalidateLotteryButton from '../admin/InvalidateLotteryButton.vue';
import LotteryInProgress from './LotteryInProgress.vue';
import LotteryResults from './LotteryResults.vue';

const props = defineProps<{
  lottery: Lottery;
  families: ApiResource<Family>[];
}>();

const isCompleted = computed(() => props.lottery.is_completed);
const isInProgress = computed(() => props.lottery.is_executing);
</script>

<template>
  <div class="flex h-full flex-col space-y-6">
    <Card class="max-w-auto flex flex-col">
      <!-- In Progress -->
      <LotteryInProgress v-if="isInProgress" class="flex-1" />

      <!-- Completed with Results -->
      <LotteryResults v-else-if="isCompleted" :families />
    </Card>

    <!-- Invalidate Button (Superadmin only) -->
    <InvalidateLotteryButton v-if="iAmSuperadmin" :lottery />
  </div>
</template>
