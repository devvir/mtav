<script setup lang="ts">
import { iAmAdmin, iAmMember } from '@/composables/useAuth';
import { LotteryManagement, PreferencesManager, ProjectPlan } from '..';

defineProps<{
  lottery: Lottery;
  units: Unit[];
  plan: Plan;
}>();
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-wide">
    <!-- Left Column: Project Plan -->
    <ProjectPlan :plan class="flex flex-col w-full p-base pb-wide" />

    <!-- Right Column: Admin Management or Member Preferences -->
    <div class="flex flex-col w-full">
      <LotteryManagement v-if="iAmAdmin" :lottery />
      <PreferencesManager v-if="iAmMember" :units />
    </div>
  </div>
</template>