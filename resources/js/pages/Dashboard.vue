<script setup lang="ts">
import Head from '@/components/Head.vue';
import Breadcrumb from '@/components/layout/header/Breadcrumb.vue';
import Breadcrumbs from '@/components/layout/header/Breadcrumbs.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
</script>

<template>
  <Head title="Project Dashboard" />

  <Breadcrumbs>
    <Breadcrumb route="home" text="Project Dashboard" />
  </Breadcrumbs>

  <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="auto-rows-min gap-4 @max-2xl:space-y-wide @2xl:grid @3xl:grid-cols-2 @6xl:grid-cols-3">
      <div
        class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
      >
        <PlaceholderPattern />
      </div>
      <div
        class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
      >
        <PlaceholderPattern />
      </div>
      <div
        class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 @max-6xl:col-span-2 dark:border-sidebar-border"
      >
        <PlaceholderPattern />
      </div>
    </div>
    <div class="relative min-h-[20vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
      <PlaceholderPattern />
    </div>
  </div>
</template>
