<script setup lang="ts">
import DropdownSample from '@/components/dropdown/DropdownSample.vue';
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import ChartSample from './playground/chart/ChartSample.vue';
import SvgBackgrounds from './playground/chart/SvgBackgrounds.vue';
import I18n from './playground/I18n.vue';
</script>

<template>
  <Head title="Dashboard" />

  <Breadcrumbs global>
    <Breadcrumb route="dev.dashboard">Dev</Breadcrumb>
    <Breadcrumb route="dev.playground" no-link>Playground</Breadcrumb>
  </Breadcrumbs>

  <div class="space-y-wide">
    <div class="rounded-2xl bg-surface-sunken p-10 text-center">
      <SvgBackgrounds />
    </div>

    <div class="rounded-2xl bg-surface-sunken p-10 text-center">
      <I18n />
    </div>

    <div class="rounded-2xl bg-surface-sunken p-10 text-center">
      <DropdownSample />
    </div>

    <div class="rounded-2xl bg-surface-sunken p-10 text-center">
      <ChartSample />
    </div>
  </div>
</template>
