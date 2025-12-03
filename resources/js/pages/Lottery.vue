<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { iAmAdmin, iAmMember } from '@/composables/useAuth';
import { LotteryHeader, LotteryManagement, PreferencesManager, ProjectPlan } from '@/components/lottery';
import LotteryExecutedStatus from '@/components/lottery/shared/LotteryExecutedStatus.vue';

const props = defineProps<{
  plan: Plan;
  lottery: Lottery;
  families: ApiResource<Family>[];
  preferences: ApiResource<Unit>[];
}>();

// Lottery is executING when it's no longer published and executED when it's soft-deleted
const isExecutedOrExecuting = computed(() => props.lottery.is_deleted || !props.lottery.is_published);
</script>

<template>
  <Head title="Lottery" />

  <Breadcrumbs>
    <Breadcrumb route="lottery" text="Lottery" />
  </Breadcrumbs>

  <div class="w-full max-w-none space-y-6">
    <LotteryHeader />

    <div class="flex flex-col @5xl:flex-row gap-wide">
      <!-- Left Column / Top: Branch based on execution state -->
      <div class="flex-1">
        <!-- Show execution status (any role) if lottery is executed/executing -->
        <LotteryExecutedStatus v-if="isExecutedOrExecuting" :lottery :families />

        <!-- Show role-specific components if lottery is not executed -->
        <template v-else>
          <LotteryManagement v-if="iAmAdmin" :lottery :families />
          <PreferencesManager v-if="iAmMember" :preferences />
        </template>
      </div>

      <!-- Right Column / Bottom: Project Plan -->
      <div class="w-full @5xl:w-full @5xl:min-w-[600px] @5xl:max-w-2/5 overflow-hidden">
        <ProjectPlan :plan />
      </div>
    </div>
  </div>
</template>
