<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import ScalingModesGrid from './plans/ScalingModesGrid.vue';
import SvgCanvasTest from './plans/SvgCanvasTest.vue';

defineProps<{
  project?: Project;
}>();
</script>

<template>
  <Head title="Spatial Plans Playground" />

  <Breadcrumbs>
    <Breadcrumb route="dev.dashboard" text="Dev" />
    <Breadcrumb route="dev.plans" text="Plans" />
  </Breadcrumbs>

  <div class="mx-auto max-w-7xl space-y-10">
    <!-- Page Header -->
    <div class="space-y-2">
      <h1 class="text-3xl font-bold">Plans Development</h1>
      <p class="text-base text-muted-foreground">
        Development environment for the SVG-based floor plan visualization.
      </p>
    </div>

    <!-- SVG Canvas Testing -->
    <SvgCanvasTest />

    <!-- Scaling Modes Testing -->
    <ScalingModesGrid />
  </div>
</template>
