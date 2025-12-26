<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import SvgCanvasTest from './plans/SvgCanvasTest.vue';
import ScalingModesGrid from './plans/ScalingModesGrid.vue';

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

  <div class="space-y-10 max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="space-y-2">
      <h1 class="text-3xl font-bold">Plans Development</h1>
      <p class="text-muted-foreground text-base">
        Development environment for the SVG-based floor plan visualization.
      </p>
    </div>

    <!-- SVG Canvas Testing -->
    <SvgCanvasTest />

    <!-- Scaling Modes Testing -->
    <ScalingModesGrid />
  </div>
</template>