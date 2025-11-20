<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import { LotteryHeader, LotteryContent } from '@/components/lottery';

defineProps<{
  lottery: Lottery;
  units: Unit[];
  plan: Plan;
}>();
</script>

<template>
  <Head title="Lottery" />

  <Breadcrumbs>
    <Breadcrumb route="lottery" text="Lottery" />
  </Breadcrumbs>

  <div class="w-full max-w-none space-y-6 pt-8">
    <LotteryHeader />
    <LotteryContent :lottery :units :plan />
  </div>
</template>